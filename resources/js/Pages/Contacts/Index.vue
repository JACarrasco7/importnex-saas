<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon, TagIcon, MapPinIcon, EnvelopeIcon, PhoneIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    contacts: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const tagFilter = ref(props.filters?.tag ?? '');
const showDelete = ref(false);
const contactToDelete = ref(null);

watch([search, tagFilter], () => {
    router.get(
        route('contacts.index'),
        {
            search: search.value || undefined,
            tag: tagFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
});

const askDelete = (contact) => {
    contactToDelete.value = contact;
    showDelete.value = true;
};

const confirmDelete = () => {
    if (!contactToDelete.value) return;
    router.delete(route('contacts.destroy', contactToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
            contactToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Contacts" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contacts</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Network" :subtitle="`${contacts.total || 0} contacts in your network`">
                    <template #actions>
                        <Link :href="route('contacts.create')" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="h-4 w-4" />
                            New Contact
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Name, email, city..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Tag</label>
                            <div class="relative">
                                <TagIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="tagFilter" type="text" placeholder="e.g. dealer, transport..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div v-if="contacts.data?.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Channels</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">City</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tags</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <Link :href="route('contacts.show', contact.id)" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ contact.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 text-sm text-gray-600">
                                            <span v-if="contact.phone" class="inline-flex items-center gap-1.5">
                                                <PhoneIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ contact.phone }}
                                            </span>
                                            <span v-if="contact.email" class="inline-flex items-center gap-1.5">
                                                <EnvelopeIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ contact.email }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="contact.city" class="inline-flex items-center gap-1 text-sm text-gray-600">
                                            <MapPinIcon class="h-3.5 w-3.5 text-gray-400" />
                                            {{ contact.city }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="contact.tags?.length" class="flex flex-wrap gap-1">
                                            <Badge v-for="tag in contact.tags" :key="tag" variant="indigo">{{ tag }}</Badge>
                                        </div>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <Link :href="route('contacts.show', contact.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600" title="View">
                                                <EyeIcon class="h-4 w-4" />
                                            </Link>
                                            <Link :href="route('contacts.edit', contact.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="Edit">
                                                <PencilIcon class="h-4 w-4" />
                                            </Link>
                                            <button type="button" @click="askDelete(contact)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else icon="🤝" title="No contacts yet" description="Add dealers, transport companies, and other contacts to your network." action-text="Add your first contact" :action-route="route('contacts.create')" />

                    <!-- Pagination -->
                    <div v-if="contacts.links && contacts.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-semibold">{{ contacts.from }}</span> to <span class="font-semibold">{{ contacts.to }}</span> of <span class="font-semibold">{{ contacts.total }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <component v-for="link in contacts.links" :key="link.label" :is="link.url ? Link : 'span'" :href="link.url || '#'" :class="[
                                'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md px-2 text-sm',
                                link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-white',
                                !link.url ? 'cursor-not-allowed opacity-50' : '',
                            ]">
                                <ChevronLeftIcon v-if="link.label.includes('Previous')" class="h-4 w-4" />
                                <ChevronRightIcon v-else-if="link.label.includes('Next')" class="h-4 w-4" />
                                <span v-else v-html="link.label"></span>
                            </component>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            title="Delete contact"
            :message="`Are you sure you want to delete ${contactToDelete?.name}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
