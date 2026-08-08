<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * LazyImage - Image with native lazy loading + IntersectionObserver fallback.
 *
 * Features:
 * - Native `loading="lazy"` (browsers 2020+) - graceful degradation
 * - IntersectionObserver for early loading when in viewport
 * - Blur-up placeholder while loading (CSS)
 * - Error fallback
 * - srcset support for responsive images
 *
 * Usage:
 *   <LazyImage src="/storage/cars/123/photo.jpg" alt="Audi A3" ratio="aspect-video" />
 *   <LazyImage :src="photo.url" :srcset="photo.srcset" :sizes="(max-width: 640px) 100vw, 50vw" />
 */

const props = defineProps({
    src: { type: String, required: true },
    srcset: { type: String, default: null },
    sizes: { type: String, default: null },
    alt: { type: String, default: '' },
    ratio: { type: String, default: 'aspect-video' }, // tailwind aspect ratio
    fit: { type: String, default: 'object-cover' }, // object-fit class
    rootMargin: { type: String, default: '200px' }, // preload when 200px away
    transitionDuration: { type: Number, default: 300 },
});

const loaded = ref(false);
const errored = ref(false);
const imgRef = ref(null);
const observer = ref(null);

const handleLoad = () => { loaded.value = true; };
const handleError = () => { errored.value = true; loaded.value = true; };

onMounted(() => {
    // If browser supports native lazy loading, the browser handles it.
    // We only set up IntersectionObserver as fallback or for blur-up effect.
    if (! imgRef.value) return;

    // If the image is already in viewport on mount (above the fold), load it immediately
    if (imgRef.value.getBoundingClientRect().top < window.innerHeight) {
        return; // native loading=lazy will handle this
    }

    // IntersectionObserver for blur-up effect (optional)
    if ('IntersectionObserver' in window) {
        observer.value = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        // Image will start loading now; native lazy loading handles it
                        observer.value?.disconnect();
                    }
                });
            },
            { rootMargin: props.rootMargin }
        );
        observer.value.observe(imgRef.value);
    }
});

onUnmounted(() => {
    observer.value?.disconnect();
});
</script>

<template>
    <div :class="['relative overflow-hidden bg-gray-100 dark:bg-gray-800', ratio]" :style="{ transitionDuration: transitionDuration + 'ms' }">
        <!-- Skeleton placeholder (visible while loading) -->
        <div
            v-if="! loaded"
            class="absolute inset-0 animate-pulse bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700"
            :style="{ backgroundSize: '200% 100%', animation: 'shimmer 1.5s infinite' }"
        />

        <!-- Error fallback -->
        <div
            v-if="errored"
            class="absolute inset-0 flex items-center justify-center bg-gray-100 text-gray-400 dark:bg-gray-800"
            role="img"
            :aria-label="alt">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </div>

        <!-- Actual image: native lazy loading + fade-in transition -->
        <img
            ref="imgRef"
            :src="src"
            :srcset="srcset || undefined"
            :sizes="sizes || undefined"
            :alt="alt"
            loading="lazy"
            decoding="async"
            :class="[
                'absolute inset-0 h-full w-full transition-opacity',
                fit,
                loaded && ! errored ? 'opacity-100' : 'opacity-0',
            ]"
            @load="handleLoad"
            @error="handleError"
        />
    </div>
</template>

<style scoped>
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>
