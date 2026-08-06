<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon, TagIcon, MapPinIcon, EnvelopeIcon, PhoneIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    contacts: Object,
    filters: Object,
});

const { t } = useTranslations();

const search = ref(props.filters?.search ?? '');
const tagFilter = ref(props.filters?.tag ?? '');
const showDelete = ref(false);
const contactToDelete = ref(null);

const filteredContacts = computed(() => {
    let result = props.contacts.data || [];
    if (search.value) {
        const term = search.value.toLowerCase();
        result = result.filter(c =>
            (c.name && c.name.toLowerCase().includes(term)) ||
            (c.email && c.email.toLowerCase().includes(term)) ||
            (c.city && c.city.toLowerCase().includes(term))
        );
    }
    if (tagFilter.value) {
        result = result.filter(c => c.tags && c.tags.some(t => t.toLowerCase().includes(tagFilter.value.toLowerCase())));
    }
    return result;
});

const stats = computed(() => {
    const contacts = props.contacts.data || [];
    return {
        total: contacts.length,
        dealers: contacts.filter(c => c.tags && c.tags.some(t => t.toLowerCase().includes('dealer'))).length,
        transport: contacts.filter(c => c.tags && c.tags.some(t => t.toLowerCase().includes('transport'))).length,
    };
});

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
    <Head :title="t('contacts.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('contacts.title') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('contacts.network_title')" :subtitle="t('contacts.subtitle', { count: contacts.total || 0 })">
                    <template #actions>
                        <Link :href="route('contacts.create')" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PlusIcon class="h-4 w-4" />
                            {{ t('contacts.add') }}
                        </Link>
                    </template>
                </PageHeader>

                <!-- Filters -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">{{ t('app.search') }}</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Nombre, email, ciudad..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-estoril-500 focus:ring-estoril-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Etiqueta</label>
                            <div class="relative">
                                <TagIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input v-model="tagFilter" type="text" placeholder="p. ej. concesionario, transporte..." class="block w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-estoril-500 focus:ring-estoril-500" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('cars.total_label') }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="rounded-xl bg-estoril-50 p-4 shadow-sm ring-1 ring-estoril-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-estoril-700">{{ t('cars.dealers') }}</div>
                        <div class="mt-1 text-2xl font-bold text-estoril-600">{{ stats.dealers }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 shadow-sm ring-1 ring-emerald-200">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ t('cars.transport') }}</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.transport }}</div>
                    </div>
                </div>

                <!-- Cards Grid -->
                <div v-if="filteredContacts.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="contact in filteredContacts"
                        :key="contact.id"
                        class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-gray-300"
                    >
                        <div class="p-5">
                            <Link :href="route('contacts.show', contact.id)" class="block">
                                <h3 class="text-base font-semibold text-gray-900 group-hover:text-estoril-600">
                                    {{ contact.name }}
                                </h3>
                                <p v-if="contact.notes" class="mt-1 text-sm text-gray-500 line-clamp-2">{{ contact.notes }}</p>
                            </Link>

                            <div class="mt-4 space-y-2 text-sm">
                                <div v-if="contact.phone" class="flex items-center gap-2 text-gray-600">
                                    <PhoneIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span>{{ contact.phone }}</span>
                                </div>
                                <div v-if="contact.email" class="flex items-center gap-2 text-gray-600">
                                    <EnvelopeIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="truncate">{{ contact.email }}</span>
                                </div>
                                <div v-if="contact.city" class="flex items-center gap-2 text-gray-600">
                                    <MapPinIcon class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span>{{ contact.city }}</span>
                                </div>
                            </div>

                            <div v-if="contact.tags && contact.tags.length > 0" class="mt-4 flex flex-wrap gap-1.5">
                                <span v-for="tag in contact.tags" :key="tag" class="inline-flex items-center rounded-md bg-estoril-50 px-2 py-0.5 text-xs font-medium text-estoril-700 ring-1 ring-inset ring-estoril-200">
                                    {{ tag }}
                                </span>
                            </div>
                        </div>

                        <div class="flex border-t border-gray-100 bg-gray-50 px-5 py-3">
                            <div class="flex-1" />
                            <div class="flex items-center gap-1">
                                <Link :href="route('contacts.show', contact.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-estoril-50 hover:text-estoril-600" :title="t('common.view')">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link :href="route('contacts.edit', contact.id)" class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" :title="t('cars.edit')">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button type="button" @click="askDelete(contact)" class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600" :title="t('cars.delete_label')">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    icon="📇"
                    :title="t('cars.no_contacts_found')"
                    :description="t('cars.no_contacts_found_desc')"
                    :primary-action="{ text: t('cars.add_first_contact'), route: route('contacts.create') }"
                    :secondary-action="{ text: t('cars.view_marketplace', 'Ver Marketplace'), route: route('marketplace.index') }"
                />
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            :title="t('contacts.delete_contact')"
            :message="t('contacts.delete_contact_message', { name: contactToDelete?.name })"
            confirm-text="Delete"
            variant="danger"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
