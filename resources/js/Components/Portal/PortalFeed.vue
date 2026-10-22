<script setup>
import { useForm } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, PaperAirplaneIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import PortalSection from '@/Components/Portal/PortalSection.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    token: { type: String, required: true },
    feed: { type: Array, required: true },
    closed: { type: Boolean, default: false },
});

const { t } = useTranslations();
const { datetime, fromNow } = useFormat();

const form = useForm({ body: '' });
const send = () => {
    form.post(route('portal.messages.store', props.token), {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
};
</script>

<template>
    <PortalSection id="mensajes" :title="t('portal.feed.title')" :subtitle="t('portal.feed.subtitle')" :icon="ChatBubbleLeftRightIcon">
        <form v-if="!closed" class="mb-5" @submit.prevent="send">
            <label for="portal-message" class="sr-only">{{ t('portal.feed.placeholder') }}</label>
            <div class="flex items-end gap-2">
                <textarea id="portal-message" v-model="form.body" rows="2" :placeholder="t('portal.feed.placeholder')" class="block w-full resize-none rounded-xl border-asphalt-200 px-4 py-3 text-base shadow-sm focus:border-estoril-500 focus:ring-estoril-500" />
                <button type="submit" :disabled="form.processing || form.body.trim().length < 2" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-estoril-700 text-white hover:bg-estoril-600 disabled:opacity-40" :aria-label="t('portal.feed.send')">
                    <PaperAirplaneIcon class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>
            <p v-if="form.errors.body" class="mt-1 text-sm text-rose-600">{{ form.errors.body }}</p>
        </form>

        <p v-if="!feed.length" class="text-sm text-asphalt-500">{{ t('portal.feed.empty') }}</p>
        <ol class="space-y-3">
            <li v-for="item in feed" :key="item.key">
                <div v-if="item.kind === 'message'" :class="['flex', item.author === 'client' ? 'justify-end' : 'justify-start']">
                    <div :class="['max-w-[85%] rounded-2xl px-4 py-2.5 text-sm', item.author === 'client' ? 'rounded-br-md bg-estoril-700 text-white' : 'rounded-bl-md bg-asphalt-100 text-asphalt-900']">
                        <p v-if="item.author_name" class="mb-0.5 text-xs font-semibold opacity-75">{{ item.author_name }}</p>
                        <p class="whitespace-pre-line">{{ item.body }}</p>
                        <p :class="['mt-1 text-[11px]', item.author === 'client' ? 'text-estoril-100' : 'text-asphalt-500']" :title="datetime(item.at)">{{ fromNow(item.at) }}</p>
                    </div>
                </div>
                <div v-else class="flex items-start gap-3 rounded-xl px-1 py-1">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-estoril-50 text-estoril-700">
                        <SparklesIcon class="h-4 w-4" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-asphalt-900">{{ item.title }}</p>
                        <p v-if="item.body" class="whitespace-pre-line text-sm text-asphalt-600">{{ item.body }}</p>
                        <p class="text-[11px] text-asphalt-400" :title="datetime(item.at)">{{ fromNow(item.at) }}</p>
                    </div>
                </div>
            </li>
        </ol>
    </PortalSection>
</template>
