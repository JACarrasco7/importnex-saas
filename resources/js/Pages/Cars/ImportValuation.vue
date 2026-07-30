<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    pending_files: Array,
});

const mode = ref('paste');
const jsonContent = ref('');
const fileInput = ref(null);
const serverPath = ref('');
const processing = ref(false);
const pendingFiles = ref([...(props.pending_files ?? [])]);

const showPaste = computed(() => mode.value === 'paste');
const showUpload = computed(() => mode.value === 'upload');
const showServer = computed(() => mode.value === 'server');
const showZip = computed(() => mode.value === 'zip');

const refreshPending = async () => {
    try {
        const response = await fetch(route('cars.import-valuation.pending'));
        const data = await response.json();
        pendingFiles.value = data.files ?? [];
    } catch (e) {
        // Silent
    }
};

onMounted(() => {
    refreshPending();
    if (pendingFiles.value.length > 0) {
        serverPath.value = pendingFiles.value[0].path;
        mode.value = 'server';
    }
});

const submitPaste = () => {
    processing.value = true;
    router.post(route('cars.import-valuation.store'), {
        mode: 'paste',
        json: jsonContent.value,
    }, {
        onFinish: () => { processing.value = false; },
    });
};

const onFileSelected = (event) => {
    fileInput.value = event.target.files[0];
    if (!fileInput.value) return;
    processing.value = true;
    const formData = new FormData();
    formData.append('mode', 'upload');
    formData.append('file', fileInput.value);
    router.post(route('cars.import-valuation.store'), formData, {
        onFinish: () => {
            processing.value = false;
            fileInput.value = null;
        },
    });
};

const submitServer = () => {
    if (!serverPath.value) return;
    processing.value = true;
    router.post(route('cars.import-valuation.store'), {
        mode: 'server',
        path: serverPath.value,
    }, {
        onFinish: () => {
            processing.value = false;
            refreshPending();
        },
    });
};

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
    <Head title="Importar informe del chat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Importar informe del chat</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Añadir coche desde informe"
                    subtitle="Pega, sube o lee el JSON generado por Claude. El coche se empareja por VIN → URL → crea nuevo." />

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            <button @click="mode = 'paste'" type="button"
                                :class="['rounded-lg px-4 py-2 text-sm font-semibold',
                                    mode === 'paste' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                                Pegar JSON
                            </button>
                            <button @click="mode = 'upload'" type="button"
                                :class="['rounded-lg px-4 py-2 text-sm font-semibold',
                                    mode === 'upload' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                                Subir archivo
                            </button>
                            <button @click="mode = 'zip'" type="button"
                                :class="['rounded-lg px-4 py-2 text-sm font-semibold',
                                    mode === 'zip' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                                Subir ZIP
                            </button>
                            <button @click="mode = 'server'" type="button"
                                :class="['rounded-lg px-4 py-2 text-sm font-semibold',
                                    mode === 'server' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                                Leer del servidor
                            </button>
                        </div>
                    </div>

                    <!-- Paste mode -->
                    <div v-if="showPaste" class="p-6">
                        <form @submit.prevent="submitPaste" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Pega aquí el JSON del informe</label>
                            <textarea v-model="jsonContent" rows="14"
                                placeholder='{ "_meta": { "schema_version": 1 }, "vehiculo": { ... } }'
                                class="block w-full rounded-lg border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-500">
                                    El informe se valida por <code class="font-mono">schema_version</code> antes de guardar.
                                </p>
                                <button type="submit" :disabled="!jsonContent.trim() || processing"
                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                                    {{ processing ? 'Importando…' : 'Importar' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Upload mode -->
                    <div v-if="showUpload" class="p-6">
                        <label class="block text-sm font-medium text-gray-700">Archivo JSON del informe</label>
                        <input type="file" accept=".json,application/json" @change="onFileSelected"
                            class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" />
                        <p class="mt-2 text-xs text-gray-500">Máx. 1 MB. Se acepta <code class="font-mono">.json</code>.</p>
                    </div>

                    <!-- ZIP mode -->
                    <div v-if="showZip" class="p-6 space-y-4">
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900">
                            <p class="font-semibold">Sube el paquete del coche y ya está</p>
                            <p class="mt-1 text-green-800">Una sola subida hace todo el trabajo:</p>
                            <ul class="mt-2 list-disc pl-5 space-y-1 text-green-800">
                                <li><code class="font-mono">informe.json</code> → crea o actualiza el coche (empareja por VIN, luego por URL del anuncio).</li>
                                <li><code class="font-mono">documentos/</code> y <code class="font-mono">publicidad/</code> → los PDFs se adjuntan al expediente, en el grupo <em>Informes IA</em>.</li>
                                <li><code class="font-mono">fotos/</code> → van a la galería del coche, en el orden del paquete.</li>
                                <li><code class="font-mono">manifest.json</code> es opcional: solo sirve para poner títulos y forzar el orden.</li>
                            </ul>
                            <p class="mt-2 text-xs text-green-700">
                                Reimportar el mismo coche sustituye fotos e informes anteriores en vez de duplicarlos.
                            </p>
                        </div>
                        <label class="block text-sm font-medium text-gray-700">Paquete .zip del coche</label>
                        <input type="file" accept=".zip,application/zip" @change="onZipSelected"
                            class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-green-700 hover:file:bg-green-100" />
                        <p class="mt-2 text-xs text-gray-500">Máx. 200 MB. Se acepta <code class="font-mono">.zip</code>.</p>
                    </div>

                    <!-- Server mode -->
                    <div v-if="showServer" class="p-6 space-y-4">
                        <div v-if="pendingFiles.length > 0">
                            <p class="text-sm font-medium text-gray-700">Informes pendientes en
                                <code class="font-mono text-xs">storage/app/importnex/import/</code>:
                            </p>
                            <ul class="mt-2 divide-y divide-gray-200 rounded-lg border border-gray-200">
                                <li v-for="f in pendingFiles" :key="f.path"
                                    class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                    <button @click="serverPath = f.path" type="button" class="flex-1 text-left">
                                        <p class="font-mono text-sm" :class="serverPath === f.path ? 'font-semibold text-indigo-700' : 'text-gray-900'">{{ f.name }}</p>
                                        <p class="text-xs text-gray-500">{{ f.size }} bytes · {{ f.mtime }}</p>
                                    </button>
                                    <span v-if="serverPath === f.path" class="text-xs font-semibold text-indigo-700">Seleccionado</span>
                                </li>
                            </ul>
                        </div>
                        <p v-else class="text-sm italic text-gray-500">
                            No hay informes pendientes. Pídele a Claude que suba el JSON al servidor y vuelve aquí.
                        </p>

                        <form @submit.prevent="submitServer" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Ruta del JSON en el servidor</label>
                            <input v-model="serverPath" type="text"
                                placeholder="/var/www/importnex-saas/storage/app/importnex/informe.json"
                                class="block w-full rounded-lg border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="flex items-center justify-between">
                                <button type="button" @click="refreshPending" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                    Refrescar lista
                                </button>
                                <button type="submit" :disabled="!serverPath || processing"
                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                                    {{ processing ? 'Importando…' : 'Importar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Cómo funciona el emparejamiento</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-amber-800">
                        <li>Si el informe trae <strong>VIN</strong> y existe un coche con ese VIN, se actualiza.</li>
                        <li>Si no, se busca por la <strong>URL del anuncio</strong>.</li>
                        <li>Si no hay coincidencia, se <strong>crea un coche nuevo</strong>.</li>
                        <li>El semáforo se recalcula automáticamente al guardar (si hay <code class="font-mono">market_avg</code>).</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
