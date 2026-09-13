<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    guias: { type: Array, default: () => [] },
    actual: { type: Object, default: () => ({}) },
});

const { t } = useTranslations();

const titulo = computed(() => props.actual?.titulo || t('nav.guide'));
</script>

<template>
    <Head :title="titulo" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('nav.guide')" :subtitle="titulo" />

                <div class="flex flex-col gap-6 lg:flex-row">
                    <!-- Índice de guías -->
                    <nav class="lg:w-72 lg:shrink-0" :aria-label="t('nav.guide')">
                        <ul
                            class="overflow-hidden rounded-xl border border-platinum-300 bg-white p-2 dark:border-asphalt-700 dark:bg-asphalt-800"
                        >
                            <li v-for="g in guias" :key="g.slug">
                                <Link
                                    :href="route('guide.show', g.slug)"
                                    class="block rounded-lg px-3 py-2 text-sm transition"
                                    :class="
                                        g.slug === actual.slug
                                            ? 'bg-estoril-700 font-semibold text-white'
                                            : 'text-asphalt-700 hover:bg-estoril-50 hover:text-estoril-800 dark:text-platinum-200 dark:hover:bg-asphalt-700 dark:hover:text-white'
                                    "
                                    :aria-current="g.slug === actual.slug ? 'page' : undefined"
                                >
                                    {{ g.titulo }}
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <!-- Contenido: markdown del repo, ya convertido a HTML en el servidor -->
                    <article
                        class="min-w-0 flex-1 overflow-hidden rounded-xl border border-platinum-300 bg-white dark:border-asphalt-700 dark:bg-asphalt-800"
                    >
                        <div
                            class="prose prose-sm max-w-none px-5 py-6 prose-headings:text-estoril-800 prose-a:text-estoril-700 prose-strong:text-asphalt-900 sm:px-7 sm:prose-base dark:prose-invert dark:prose-headings:text-platinum-100 dark:prose-a:text-estoril-300"
                            v-html="actual.html"
                        />
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
