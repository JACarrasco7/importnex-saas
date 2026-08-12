<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, PencilIcon, EnvelopeIcon, PhoneIcon, MapPinIcon, TagIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    contact: Object,
});
</script>

<template>
    <Head :title="contact.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ contact.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="contact.name" :subtitle="t('contacts.subtitle_details')">
                    <template #actions>
                        <Link :href="route('contacts.index')" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                        <Link :href="route('contacts.edit', contact.id)" class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-500">
                            <PencilIcon class="h-4 w-4" />
                            {{ t('common.edit') }}
                        </Link>
                    </template>
                </PageHeader>

                <FormSection :title="t('contacts.section_contact')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div v-if="contact.phone">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('contacts.field_phone') }}</dt>
                            <dd class="mt-1 inline-flex items-center gap-1.5 text-sm text-gray-900">
                                <PhoneIcon class="h-4 w-4 text-gray-400" /> {{ contact.phone }}
                            </dd>
                        </div>
                        <div v-if="contact.email">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('contacts.field_email') }}</dt>
                            <dd class="mt-1 inline-flex items-center gap-1.5 text-sm text-gray-900">
                                <EnvelopeIcon class="h-4 w-4 text-gray-400" /> {{ contact.email }}
                            </dd>
                        </div>
                        <div v-if="contact.city">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('contacts.field_city') }}</dt>
                            <dd class="mt-1 inline-flex items-center gap-1.5 text-sm text-gray-900">
                                <MapPinIcon class="h-4 w-4 text-gray-400" /> {{ contact.city }}
                            </dd>
                        </div>
                        <div v-if="contact.tags?.length">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('contacts.field_tags') }}</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                <Badge v-for="tag in contact.tags" :key="tag" variant="indigo">{{ tag }}</Badge>
                            </dd>
                        </div>
                        <div v-if="contact.notes" class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ t('contacts.field_notes') }}</dt>
                            <dd class="mt-1 text-sm text-gray-700">{{ contact.notes }}</dd>
                        </div>
                    </div>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
