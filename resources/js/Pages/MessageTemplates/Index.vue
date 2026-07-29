<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { EnvelopeIcon, EyeIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Toast from '@/Components/Toast.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    templates: Object,
    filters: Object,
});

const toast = ref({ show: false, type: 'success', message: '' });

const copyToClipboard = async (content) => {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(content);
        } else {
            const ta = document.createElement('textarea');
            ta.value = content;
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
    <Head title="Message Templates" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Message Templates</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Templates" :subtitle="`${templates.total || 0} reusable messages`" />

                <EmptyState v-if="!templates.data?.length" icon="📨" title="No templates yet" description="Create reusable message templates to speed up client communication." />

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Template</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Language</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="tpl in templates.data" :key="tpl.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50">
                                                <EnvelopeIcon class="h-5 w-5 text-indigo-600" />
                                            </div>
                                            <span class="font-medium text-gray-900">{{ tpl.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><Badge :variant="langVariant(tpl.language)">{{ tpl.language }}</Badge></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ tpl.category }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button @click="copyToClipboard(tpl.content)" class="rounded-md p-1.5 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600" title="Copy">
                                                <ClipboardDocumentIcon class="h-4 w-4" />
                                            </button>
                                            <Link :href="route('message-templates.show', tpl.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="View">
                                                <EyeIcon class="h-4 w-4" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed bottom-4 right-4 z-50">
            <Toast :show="toast.show" :type="toast.type" :message="toast.message" />
        </div>
    </AuthenticatedLayout>
</template>
