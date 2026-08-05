<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, ClipboardDocumentIcon, EnvelopeIcon, LanguageIcon, TagIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    template: Object,
});

const toast = ref({ show: false, type: 'success', message: '' });

const copyToClipboard = async () => {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(props.template.content);
        } else {
            const ta = document.createElement('textarea');
            ta.value = props.template.content;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        toast.value = { show: true, type: 'success', message: 'Copied to clipboard' };
        setTimeout(() => (toast.value.show = false), 2000);
    } catch (e) {
        toast.value = { show: true, type: 'error', message: 'Failed to copy' };
    }
};

const langVariant = (lang) => {
    const map = { en: 'blue', es: 'red', de: 'amber', fr: 'purple' };
    return map[lang?.toLowerCase()] || 'gray';
};
</script>

<template>
    <Head :title="template.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ template.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="template.name" :subtitle="template.description || 'Message template'">
                    <template #actions>
                        <Link :href="route('message-templates.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back
                        </Link>
                        <button @click="copyToClipboard" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <ClipboardDocumentIcon class="h-4 w-4" />
                            Copy
                        </button>
                    </template>
                </PageHeader>

                <FormSection :title="t('cars.metadata')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <LanguageIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.language') }}</dt>
                                <dd class="mt-1"><Badge :variant="langVariant(template.language)">{{ template.language }}</Badge></dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-estoril-50">
                                <TagIcon class="h-5 w-5 text-estoril-600" />
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.category') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ template.category }}</dd>
                            </div>
                        </div>
                    </div>
                </FormSection>

                <FormSection :title="t('cars.content')">
                    <pre class="whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-sm text-gray-900 ring-1 ring-gray-200">{{ template.content }}</pre>
                </FormSection>

                <FormSection v-if="template.placeholders?.length" :title="t('cars.placeholders')">
                    <div class="flex flex-wrap gap-2">
                        <Badge v-for="ph in template.placeholders" :key="ph" variant="gray">{{ ph }}</Badge>
                    </div>
                </FormSection>
            </div>
        </div>

        <div class="fixed bottom-4 right-4 z-50">
            <Toast :show="toast.show" :type="toast.type" :message="toast.message" />
        </div>
    </AuthenticatedLayout>
</template>
