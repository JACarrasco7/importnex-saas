<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    order: { type: Object, required: true },
    media: { type: Array, required: true },
    collections: { type: Array, required: true },
});

const { t } = useTranslations();
const fileInput = ref(null);
const form = useForm({ files: [], collection: 'inspection', visibility: 'client', caption: '' });

const onFiles = (event) => { form.files = Array.from(event.target.files || []); };
const upload = () => {
    form.post(route('import-orders.media.store', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('files', 'caption');
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};
const toggleVisibility = (item) => {
    router.patch(route('import-orders.media.update', [props.order.id, item.id]), {
        visibility: item.visibility === 'client' ? 'internal' : 'client',
    }, { preserveScroll: true });
};
const remove = (item) => {
    if (!confirm(t('orders.media.confirm_delete'))) return;
    router.delete(route('import-orders.media.destroy', [props.order.id, item.id]), { preserveScroll: true });
};
const input = 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-estoril-500 focus:ring-estoril-500 dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100';
</script>

<template>
    <OrderCard id="fotos" :title="t('orders.media.title')" :icon="PhotoIcon">
        <form class="grid grid-cols-1 gap-2 rounded-xl bg-gray-50 p-3 sm:grid-cols-2 dark:bg-asphalt-900" @submit.prevent="upload">
            <input ref="fileInput" type="file" multiple accept="image/*,application/pdf,video/mp4,video/quicktime" required class="text-xs sm:col-span-2" :aria-label="t('orders.media.files')" @change="onFiles" />
            <select v-model="form.collection" :aria-label="t('orders.media.collection')" :class="input">
                <option v-for="c in collections" :key="c" :value="c">{{ t('orders.media.collections.' + c) }}</option>
            </select>
            <select v-model="form.visibility" :aria-label="t('orders.media.visibility')" :class="input">
                <option value="client">{{ t('orders.media.visible_client') }}</option>
                <option value="internal">{{ t('orders.media.internal') }}</option>
            </select>
            <input v-model="form.caption" type="text" :placeholder="t('orders.media.caption')" :aria-label="t('orders.media.caption')" :class="[input, 'sm:col-span-2']" />
            <p v-for="(error, key) in form.errors" :key="key" class="text-xs text-rose-600 sm:col-span-2">{{ error }}</p>
            <button type="submit" :disabled="form.processing || !form.files.length" class="rounded-lg bg-estoril-700 px-3 py-2 text-xs font-semibold text-white hover:bg-estoril-600 disabled:opacity-50 sm:col-span-2">
                {{ form.processing ? t('orders.media.uploading') : t('orders.media.upload') }}
            </button>
        </form>

        <p v-if="!media.length" class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ t('orders.media.none') }}</p>
        <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <li v-for="item in media" :key="item.id" class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-asphalt-700">
                <a :href="item.url" target="_blank" rel="noopener" class="block bg-gray-100 dark:bg-asphalt-900">
                    <img v-if="item.is_image" :src="item.url" :alt="item.caption || item.name" class="aspect-square w-full object-cover" loading="lazy" />
                    <span v-else class="flex aspect-square items-center justify-center p-2 text-center text-xs text-gray-600">{{ item.name }}</span>
                </a>
                <div class="flex items-center justify-between gap-1 px-2 py-1.5">
                    <span :class="['truncate text-[11px] font-semibold', item.visibility === 'client' ? 'text-emerald-700' : 'text-gray-500']">
                        {{ item.visibility === 'client' ? t('orders.media.badge_client') : t('orders.media.badge_internal') }}
                    </span>
                    <span class="flex shrink-0 gap-1">
                        <button type="button" class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-estoril-700" :aria-label="t('orders.media.toggle')" :title="t('orders.media.toggle')" @click="toggleVisibility(item)">
                            <EyeSlashIcon v-if="item.visibility === 'client'" class="h-4 w-4" aria-hidden="true" />
                            <EyeIcon v-else class="h-4 w-4" aria-hidden="true" />
                        </button>
                        <button type="button" class="rounded p-1 text-gray-500 hover:bg-rose-50 hover:text-rose-600" :aria-label="t('orders.media.delete')" @click="remove(item)">
                            <TrashIcon class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </span>
                </div>
            </li>
        </ul>
    </OrderCard>
</template>
