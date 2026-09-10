<script setup>
import { ref, computed, watch } from 'vue';
import {
    ArrowDownTrayIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    car: { type: Object, required: true },
    photoType: { type: String, default: 'exterior' },
    photoFiles: { type: Array, default: () => [] },
    uploadProgress: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:photoType',
    'update:photoFiles',
    'submit-photos',
    'ask-delete-photo',
]);

const updatePhotoType = (v) => emit('update:photoType', v);

const handlePhotoFiles = (event) => {
    emit('update:photoFiles', event.target.files);
};

const submitPhotos = () => emit('submit-photos');

// Lightbox
const lightboxIndex = ref(-1);
const lightboxPhotos = computed(() => props.car.photos || []);
const openLightbox = (index) => { lightboxIndex.value = index; };
const closeLightbox = () => { lightboxIndex.value = -1; };
const nextLightbox = () => {
    if (lightboxPhotos.value.length === 0) return;
    lightboxIndex.value = (lightboxIndex.value + 1) % lightboxPhotos.value.length;
};
const prevLightbox = () => {
    if (lightboxPhotos.value.length === 0) return;
    lightboxIndex.value = (lightboxIndex.value - 1 + lightboxPhotos.value.length) % lightboxPhotos.value.length;
};

// Sync uploadProgress=false si cambia
watch(() => props.uploadProgress, (v) => { /* re-render del botón submit */ });
</script>

<template>
    <div class="space-y-6">
        <!-- Photos upload + grid -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Photos</h3>
                <span class="text-sm text-gray-500">{{ car.photos?.length || 0 }} files</span>
            </div>
            <div class="p-6 space-y-4">
                <form @submit.prevent="submitPhotos" class="grid grid-cols-1 gap-3 rounded-xl bg-gray-50 p-4 sm:grid-cols-[1fr_2fr_auto]">
                    <select :value="photoType" @change="(e) => updatePhotoType(e.target.value)" class="block rounded-lg border-gray-300 text-sm focus:border-estoril-500 focus:ring-estoril-500">
                        <option value="exterior">{{ t('cars.photo_type_exterior') }}</option>
                        <option value="interior">{{ t('cars.photo_type_interior') }}</option>
                        <option value="engine">{{ t('cars.photo_type_engine') }}</option>
                        <option value="defect">{{ t('cars.photo_type_defect') }}</option>
                        <option value="document">{{ t('cars.photo_type_document') }}</option>
                    </select>
                    <input type="file" multiple accept="image/*" @change="handlePhotoFiles" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-estoril-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-estoril-700 hover:file:bg-estoril-100" />
                    <button type="submit" :disabled="!photoFiles.length || uploadProgress" class="inline-flex items-center justify-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50">
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        {{ uploadProgress ? t('cars.uploading') : t('cars.upload') }}
                    </button>
                </form>

                <div v-if="car.photos?.length" class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div v-for="(photo, idx) in car.photos" :key="photo.id" class="group relative overflow-hidden rounded-lg">
                        <button type="button" @click="openLightbox(idx)" class="block h-full w-full">
                            <img :src="`/storage/${photo.url}`" :alt="photo.photo_type" class="h-32 w-full cursor-zoom-in object-cover" loading="lazy" />
                        </button>
                        <button type="button" @click.stop="emit('ask-delete-photo', photo)" class="absolute right-1.5 top-1.5 rounded-md bg-rose-600/90 p-1.5 text-white opacity-0 shadow transition hover:bg-rose-600 group-hover:opacity-100" :title="t('common.delete')">
                            <TrashIcon class="h-3.5 w-3.5" />
                        </button>
                        <span class="absolute bottom-2 left-2 rounded bg-black/70 px-2 py-0.5 text-xs text-white">{{ photo.photo_type }}</span>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-gray-500">{{ t('cars.no_photos_yet') }}</p>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxIndex >= 0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" @click="closeLightbox">
                <button type="button" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" @click.stop="closeLightbox">
                    <XMarkIcon class="h-6 w-6" />
                </button>
                <button v-if="lightboxPhotos.length > 1" type="button" class="absolute left-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" @click.stop="prevLightbox">‹</button>
                <img v-if="lightboxPhotos[lightboxIndex]" :src="`/storage/${lightboxPhotos[lightboxIndex].url}`" :alt="lightboxPhotos[lightboxIndex].photo_type" class="max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl" @click.stop />
                <button v-if="lightboxPhotos.length > 1" type="button" class="absolute right-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" @click.stop="nextLightbox">›</button>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-4 py-1.5 text-sm text-white">
                    {{ lightboxIndex + 1 }} / {{ lightboxPhotos.length }}
                </div>
            </div>
        </Teleport>
    </div>
</template>
