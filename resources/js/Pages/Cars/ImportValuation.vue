<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const processing = ref(false);
const dragOver = ref(false);
const fileInput = ref(null);

const flash = computed(() => usePage().props.value?.flash ?? {});
const batchResult = computed(() => flash.value?.batch_result ?? null);

const uploadFiles = (files) => {
    if (!files || files.length === 0) return;
    processing.value = true;
    const formData = new FormData();
    formData.append('mode', files.length > 1 ? 'batch' : 'upload');
    for (const f of files) {
        formData.append('file[]', f);
    }
    router.post(route('cars.import-valuation.store'), formData, {
        onFinish: () => {
            processing.value = false;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const onFilesSelected = (event) => {
    const files = Array.from(event.target.files || []);
    uploadFiles(files);
};

const onDrop = (event) => {
    event.preventDefault();
    dragOver.value = false;
    const files = Array.from(event.dataTransfer?.files || []);
    uploadFiles(files);
};

const onDragOver = (event) => {
    event.preventDefault();
    dragOver.value = true;
};

const onDragLeave = () => {
    dragOver.value = false;
};

const openFilePicker = () => {
    fileInput.value?.click();
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

                <div v-if="batchResult" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="p-6 space-y-4">
                        <div :class="[
                            'flex items-center justify-between rounded-xl border p-4',
                            batchResult.ok
                                ? 'border-green-200 bg-green-50 text-green-900'
                                : 'border-red-200 bg-red-50 text-red-900',
                        ]">
                            <div>
                                <p class="font-semibold">
                                    {{ batchResult.summary }}
                                </p>
                                <p class="text-xs opacity-75 mt-1">
                                    {{ batchResult.processed }} OK · {{ batchResult.failed }} con error
                                </p>
                            </div>
                            <span :class="[
                                'inline-flex h-10 w-10 items-center justify-center rounded-full text-2xl font-bold',
                                batchResult.ok ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800',
                            ]">
                                {{ batchResult.processed }}
                            </span>
                        </div>

                        <ul class="divide-y divide-gray-100">
                            <li v-for="(r, idx) in batchResult.results" :key="idx"
                                class="flex items-center justify-between py-3 text-sm">
                                <div class="flex-1 min-w-0">
                                    <p class="font-mono text-gray-900 truncate" :title="r.basename">
                                        {{ r.basename }}
                                    </p>
                                    <p v-if="r.ok" class="text-xs text-gray-500">
                                        coche #{{ r.car_id }} · {{ r.was_new ? 'nuevo' : 'actualizado' }}
                                        · {{ r.photos }} fotos
                                        · <a :href="`/cars/${r.car_id}`"
                                            class="text-estoril-700 underline">ver</a>
                                    </p>
                                    <p v-else class="text-xs text-red-600">
                                        {{ r.error }}
                                    </p>
                                    <ul v-if="r.warnings && r.warnings.length" class="mt-1 text-xs text-amber-700 list-disc pl-4">
                                        <li v-for="(w, i) in r.warnings" :key="i">{{ w }}</li>
                                    </ul>
                                </div>
                                <span :class="[
                                    'ml-3 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    r.ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                ]">
                                    {{ r.ok ? 'OK' : 'ERROR' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

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
                            <div
                                @click="openFilePicker"
                                @drop="onDrop"
                                @dragover="onDragOver"
                                @dragleave="onDragLeave"
                                :class="[
                                    'mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition-colors',
                                    dragOver
                                        ? 'border-estoril-500 bg-estoril-50'
                                        : 'border-gray-300 bg-gray-50 hover:border-estoril-400 hover:bg-estoril-50',
                                    processing ? 'pointer-events-none opacity-60' : '',
                                ]"
                                data-test="valuation-drop-zone">
                                <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7m-2-4H5a2 2 0 00-2 2v0a2 2 0 002 2h14a2 2 0 002-2v0a2 2 0 00-2-2H5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-3-3v6" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-700">
                                    <span class="font-semibold text-estoril-700">Arrastra</span> uno o varios ZIPs aquí
                                </p>
                                <p class="mt-1 text-xs text-gray-500">o haz clic para seleccionarlos</p>
                                <p v-if="processing" class="mt-2 text-xs text-estoril-600">{{ t('cars.processing_dots') }}</p>
                            </div>
                            <input ref="fileInput" type="file" multiple
                                accept=".zip,application/zip,.json,application/json"
                                @change="onFilesSelected" class="hidden" />
                            <p class="mt-2 text-xs text-gray-500">
                                {{ t('cars.upload_max_size') }} <code class="font-mono">.zip</code>.
                                Modo batch: best-effort, los ZIPs con error no bloquean al resto.
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
