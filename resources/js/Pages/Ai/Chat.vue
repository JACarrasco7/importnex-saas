<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PaperAirplaneIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    providers: Array,
    current: Object,
});

const messages = ref([
    { role: 'user', content: '' },
]);
const sending = ref(false);
const errorMsg = ref('');

const providerLabel = computed(() => {
    if (!props.current?.provider) return t('ai_chat.disabled');
    return props.providers.find(p => p.key === props.current.provider)?.label ?? props.current.provider;
});

function addMessage() {
    messages.value.push({ role: 'user', content: '' });
}

function removeMessage(idx) {
    if (messages.value.length > 1) messages.value.splice(idx, 1);
}

async function send() {
    errorMsg.value = '';
    if (!props.current?.has_key) {
        errorMsg.value = t('ai_chat.no_provider');
        return;
    }
    sending.value = true;
    try {
        const payload = {
            messages: messages.value.filter(m => m.content.trim() !== ''),
        };
        const resp = await window.axios.post(route('ai.chat.send'), payload);
        if (resp.data.success) {
            messages.value.push({ role: 'assistant', content: resp.data.text });
        } else {
            errorMsg.value = resp.data.error || t('ai_chat.call_failed');
        }
    } catch (e) {
        errorMsg.value = e.response?.data?.error || e.message || t('ai_chat.unknown_error');
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <Head :title="t('nav.ai_chat')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('nav.ai_chat') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('ai_chat.title')"
                            :subtitle="`${t('ai_chat.using')} ${providerLabel}${current?.model ? ' · ' + current.model : ''}`">
                </PageHeader>

                <div class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-200">
                    <div v-if="!current?.has_key" class="mb-4 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                        <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-shrink-0" />
                        <div>
                            {{ t('ai_chat.no_provider_banner') }}
                            <a :href="route('organization.edit', { organization: 'current' })" class="font-semibold underline">{{ t('ai_chat.configure_now') }}</a>.
                        </div>
                    </div>

                    <div v-if="errorMsg" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                        {{ errorMsg }}
                    </div>

                    <div class="space-y-4">
                        <div v-for="(msg, idx) in messages" :key="idx"
                             :class="['rounded-lg p-4 ring-1', msg.role === 'assistant' ? 'bg-estoril-50 ring-estoril-200' : 'bg-gray-50 ring-gray-200']">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide"
                                      :class="msg.role === 'assistant' ? 'text-estoril-700' : 'text-gray-500'">
                                    {{ msg.role }}
                                </span>
                                <button v-if="msg.role === 'user' && messages.length > 1" @click="removeMessage(idx)"
                                        class="text-xs text-red-600 hover:text-red-800">{{ t('common.remove') }}</button>
                            </div>
                            <textarea v-if="msg.role === 'user'" v-model="msg.content" rows="3"
                                      class="mt-2 block w-full rounded border-gray-300 text-sm"
                                      :placeholder="t('ai_chat.message_placeholder')"></textarea>
                            <pre v-else class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ msg.content }}</pre>
                        </div>

                        <button @click="addMessage" type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                            + {{ t('ai_chat.add_message') }}
                        </button>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="send" :disabled="sending || !current?.has_key"
                                class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                            <PaperAirplaneIcon class="h-4 w-4" />
                            {{ sending ? t('ai_chat.sending') : t('ai_chat.send_to', { provider: providerLabel }) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
