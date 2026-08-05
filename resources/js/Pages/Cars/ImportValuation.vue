<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const processing = ref(false);

const onZipSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    processing.value = true;
    const formData = new FormData();
    formData.append('mode', 'zip');
    formData.append('file', file);
    router.post(route('cars.import-valuation.store'), formData, {
        onFinish: () => { processing.value = false; },
    });
};
</script>

<template>
    <Head :title="t('cars.upload_car_package')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('cars.upload_car_package') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.upload_car_package_title')"
                    :subtitle="t('cars.upload_package_subtitle')" />

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="p-6 space-y-5">
                        <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-900">
                            <p class="font-semibold">{{ t('cars.upload_car_package_desc') }}</p>
                            <p class="mt-1 text-green-800">{{ t('cars.upload_does_everything') }}</p>
                            <ul class="mt-3 list-disc pl-5 space-y-1.5 text-green-800">
                                <li><code class="font-mono">informe.json</code> → {{ t('cars.upload_informe_desc') }}</li>
                                <li><code class="font-mono">documentos/</code> {{ t('cars.upload_and') }} <code class="font-mono">publicidad/</code> → {{ t('cars.upload_docs_desc') }}</li>
                                <li><code class="font-mono">fotos/</code> → {{ t('cars.upload_fotos_desc') }}</li>
                                <li><code class="font-mono">manifest.json</code> {{ t('cars.upload_manifest_desc') }}</li>
                            </ul>
                            <p class="mt-3 text-xs text-green-700">
                                {{ t('cars.upload_reimport_desc') }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ t('cars.zip_package') }}</label>
                            <input type="file" accept=".zip,application/zip" @change="onZipSelected" :disabled="processing"
                                class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-green-700 hover:file:bg-green-100 disabled:opacity-50" />
                            <p class="mt-2 text-xs text-gray-500">
                                {{ t('cars.upload_max_size') }} <code class="font-mono">.zip</code>.
                                <span v-if="processing" class="ml-2 text-estoril-600">{{ t('cars.processing_dots') }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">{{ t('cars.create_manually') }}</p>
                    <p class="mt-1 text-amber-800">
                        {{ t('cars.create_manually_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
