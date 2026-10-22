<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, PhoneIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    token: { type: String, required: true },
    contact: { type: Object, required: true },
    canRequestCancellation: { type: Boolean, default: false },
    cancellationRequestedAt: { type: String, default: null },
});

const { t } = useTranslations();
const { date } = useFormat();

const showCancel = ref(false);
const form = useForm({ reason: '' });
const requestCancellation = () => {
    form.post(route('portal.cancellation', props.token), {
        preserveScroll: true,
        onSuccess: () => { showCancel.value = false; },
    });
};
</script>

<template>
    <section class="rounded-2xl bg-asphalt-900 p-5 text-white shadow-sm sm:p-6">
        <div class="flex items-center gap-3">
            <UserCircleIcon class="h-10 w-10 text-platinum-400" aria-hidden="true" />
            <div>
                <p class="text-xs uppercase tracking-wide text-platinum-400">{{ t('portal.contact.your_manager') }}</p>
                <p class="text-base font-semibold">{{ contact.manager_name || 'JJ Import Motors' }}</p>
                <p class="text-xs text-platinum-400">{{ contact.schedule }}</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <a :href="contact.whatsapp_url" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>
                {{ t('portal.contact.whatsapp') }}
            </a>
            <a :href="`tel:${contact.phone.replace(/\s+/g, '')}`" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold hover:bg-white/20">
                <PhoneIcon class="h-5 w-5" aria-hidden="true" />
                {{ contact.phone }}
            </a>
            <a :href="`mailto:${contact.email}`" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold hover:bg-white/20">
                <EnvelopeIcon class="h-5 w-5" aria-hidden="true" />
                {{ t('portal.contact.email') }}
            </a>
        </div>

        <div class="mt-5 border-t border-white/10 pt-4 text-sm">
            <p v-if="cancellationRequestedAt" class="text-platinum-400">{{ t('portal.contact.cancel_requested', { date: date(cancellationRequestedAt) }) }}</p>
            <template v-else-if="canRequestCancellation">
                <button type="button" class="text-platinum-400 underline-offset-2 hover:text-white hover:underline" @click="showCancel = !showCancel">
                    {{ t('portal.contact.cancel_link') }}
                </button>
                <form v-if="showCancel" class="mt-3 space-y-2" @submit.prevent="requestCancellation">
                    <label for="cancel-reason" class="block text-platinum-400">{{ t('portal.contact.cancel_label') }}</label>
                    <textarea id="cancel-reason" v-model="form.reason" rows="3" required class="block w-full rounded-xl border-0 bg-white/10 px-4 py-3 text-white placeholder-platinum-400 focus:ring-2 focus:ring-white" />
                    <p v-if="form.errors.reason || form.errors.general" class="text-rose-300">{{ form.errors.reason || form.errors.general }}</p>
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-white px-4 py-2 font-semibold text-asphalt-900 hover:bg-platinum-100 disabled:opacity-50">
                        {{ t('portal.contact.cancel_send') }}
                    </button>
                </form>
            </template>
        </div>
    </section>
</template>
