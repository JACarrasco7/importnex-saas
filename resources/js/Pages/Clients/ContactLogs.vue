<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, CheckIcon, TrashIcon, ChatBubbleLeftRightIcon, PhoneIcon, EnvelopeIcon, DevicePhoneMobileIcon, MapPinIcon, UserIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import FormField from '@/Components/FormField.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useFormat } from '@/Composables/useFormat';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: Object,
    logs: Object,
});

const form = useForm({
    contact_date: new Date().toISOString().slice(0, 10),
    channel: 'phone',
    summary: '',
});

const showDelete = ref(false);
const logToDelete = ref(null);

const { date } = useFormat();

const submit = () => form.post(route('clients.contact-logs.store', props.client.id), {
    onSuccess: () => form.reset('summary'),
    preserveScroll: true,
});

const askDelete = (log) => { logToDelete.value = log; showDelete.value = true; };
const confirmDelete = () => {
    if (!logToDelete.value) return;
    router.delete(route('clients.contact-logs.destroy', [props.client.id, logToDelete.value.id]), {
        preserveScroll: true,
        onSuccess: () => { showDelete.value = false; logToDelete.value = null; },
    });
};

const channelOptions = [
    { value: 'phone', label: 'Phone', icon: PhoneIcon },
    { value: 'email', label: 'Email', icon: EnvelopeIcon },
    { value: 'whatsapp', label: 'WhatsApp', icon: DevicePhoneMobileIcon },
    { value: 'sms', label: 'SMS', icon: DevicePhoneMobileIcon },
    { value: 'in_person', label: 'In person', icon: MapPinIcon },
    { value: 'other', label: 'Other', icon: UserIcon },
];

const channelVariant = (channel) => {
    const map = { phone: 'green', email: 'blue', whatsapp: 'emerald', sms: 'amber', in_person: 'purple', other: 'gray' };
    return map[channel] || 'gray';
};

const channelIcon = (channel) => {
    const opt = channelOptions.find(c => c.value === channel);
    return opt ? opt.icon : ChatBubbleLeftRightIcon;
};

const inputClass = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500';
</script>

<template>
    <Head :title="`Contact logs: ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contact logs: {{ client.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="`Contact logs`" :subtitle="`Communication history for ${client.name}`">
                    <template #actions>
                        <Link :href="route('clients.show', client.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back_to_client') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- New log form -->
                <FormSection title="Log new contact">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField label="Date" required>
                                <input v-model="form.contact_date" type="date" required :class="inputClass" />
                            </FormField>
                            <FormField label="Channel" required>
                                <select v-model="form.channel" required :class="inputClass">
                                    <option v-for="opt in channelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </FormField>
                        </div>
                        <FormField label="Summary" required>
                            <textarea v-model="form.summary" rows="3" required :class="inputClass" placeholder="What was discussed?" />
                        </FormField>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500 disabled:opacity-50">
                                <CheckIcon class="h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Log contact' }}
                            </button>
                        </div>
                    </form>
                </FormSection>

                <!-- Logs list -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">History</h3>
                    </div>
                    <EmptyState v-if="!logs?.data?.length" icon="💬" :title="t('cars.no_contact_logs_yet')" :description="t('cars.no_contact_logs_desc')" />
                    <div v-else class="divide-y divide-gray-200">
                        <div v-for="log in logs.data" :key="log.id" class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-estoril-50">
                                <component :is="channelIcon(log.channel)" class="h-5 w-5 text-estoril-600" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <Badge :variant="channelVariant(log.channel)">{{ log.channel }}</Badge>
                                    <span class="text-xs text-gray-500">{{ date(log.contact_date) }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-900">{{ log.summary }}</p>
                            </div>
                            <button @click="askDelete(log)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            :title="t('clients.delete_log')"
            :message="t('clients.delete_log_message')"
            :confirm-text="t('common.delete')"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
