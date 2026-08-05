<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    ArrowLeftIcon,
    SparklesIcon,
    CheckCircleIcon,
    XMarkIcon,
    DocumentDuplicateIcon,
    MegaphoneIcon,
    PhotoIcon,
    HashtagIcon,
    LightBulbIcon,
    DocumentIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';
import PreviewMilanuncios from '@/Components/PreviewMilanuncios.vue';
import PreviewCochesNet from '@/Components/PreviewCochesNet.vue';
import PreviewWallapop from '@/Components/PreviewWallapop.vue';
import PreviewTikTok from '@/Components/PreviewTikTok.vue';
import PreviewInstagram from '@/Components/PreviewInstagram.vue';
import PreviewFacebook from '@/Components/PreviewFacebook.vue';

const props = defineProps({
    car: Object,
    contents: Array,
});

const { formatCurrency } = useFormat();

const CHANNELS = [
    { key: 'milanuncios', label: 'Milanuncios', icon: '🛒', type: 'portal' },
    { key: 'coches_net', label: 'Coches.net', icon: '🚗', type: 'portal' },
    { key: 'wallapop', label: 'Wallapop', icon: '📱', type: 'portal' },
    { key: 'tiktok', label: 'TikTok', icon: '🎵', type: 'social' },
    { key: 'instagram', label: 'Instagram', icon: '📷', type: 'social' },
    { key: 'facebook', label: 'Facebook', icon: '📘', type: 'social' },
];

const activeChannel = ref('milanuncios');
const generating = ref(false);
const saving = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const showPreview = ref(false);

// Find existing content for the active channel
const currentContent = computed(() => {
    return props.contents.find(c => c.channel === activeChannel.value) || null;
});

// Editable form fields
const form = ref({
    title: '',
    description: '',
    hashtags: [],
    photo_tips: [],
});

const newHashtag = ref('');
const newTip = ref('');

function loadContent() {
    if (currentContent.value) {
        form.value = {
            title: currentContent.value.title || '',
            description: currentContent.value.description || '',
            hashtags: currentContent.value.hashtags || [],
            photo_tips: currentContent.value.photo_tips || [],
        };
    } else {
        form.value = {
            title: '',
            description: '',
            hashtags: [],
            photo_tips: [],
        };
    }
}

watch(activeChannel, loadContent);
loadContent();

function togglePreview() {
    showPreview.value = !showPreview.value;
}

function generate() {
    generating.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    router.post(
        route('cars.marketing.generate', props.car.id),
        { channel: activeChannel.value },
        {
            preserveScroll: true,
            onFinish: () => { generating.value = false; },
            onError: (e) => { errorMsg.value = e?.message || 'Error generando contenido.'; },
            onSuccess: () => {
                successMsg.value = 'Contenido generado.';
                // Reload props
                router.reload({ only: ['contents'] });
            },
        },
    );
}

function save() {
    saving.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    router.post(
        route('cars.marketing.save', props.car.id),
        {
            channel: activeChannel.value,
            title: form.value.title,
            description: form.value.description,
            hashtags: form.value.hashtags,
            photo_tips: form.value.photo_tips,
        },
        {
            preserveScroll: true,
            onFinish: () => { saving.value = false; },
            onError: (e) => { errorMsg.value = e?.message || 'Error guardando.'; },
            onSuccess: () => {
                successMsg.value = 'Borrador guardado.';
                router.reload({ only: ['contents'] });
            },
        },
    );
}

function publish() {
    router.post(
        route('cars.marketing.publish', props.car.id),
        { channel: activeChannel.value },
        {
            preserveScroll: true,
            onError: (e) => { errorMsg.value = e?.message || 'Error publicando.'; },
            onSuccess: () => {
                successMsg.value = 'Marcado como publicado.';
                router.reload({ only: ['contents'] });
            },
        },
    );
}

function addHashtag() {
    if (newHashtag.value.trim() && !form.value.hashtags.includes(newHashtag.value.trim())) {
        form.value.hashtags.push(newHashtag.value.trim());
    }
    newHashtag.value = '';
}

function removeHashtag(tag) {
    form.value.hashtags = form.value.hashtags.filter(t => t !== tag);
}

function addTip() {
    if (newTip.value.trim()) {
        form.value.photo_tips.push(newTip.value.trim());
    }
    newTip.value = '';
}

function removeTip(index) {
    form.value.photo_tips.splice(index, 1);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    successMsg.value = 'Copiado al portapapeles.';
}

function copyAll() {
    const content = [];
    if (form.value.title) content.push(`Título: ${form.value.title}`);
    if (form.value.description) content.push(`Descripción: ${form.value.description}`);
    if (form.value.hashtags.length) content.push(`Hashtags: ${form.value.hashtags.map(t => `#${t}`).join(' ')}`);
    if (form.value.photo_tips.length) content.push(`Tips de fotos: ${form.value.photo_tips.join(', ')}`);
    const text = content.join('\n\n');
    navigator.clipboard.writeText(text);
    successMsg.value = 'Todo copiado al portapapeles.';
}

function channelLabel(channel) {
    return CHANNELS.find(c => c.key === channel)?.label || channel;
}

function renderPreview() {
    const channelComponents = {
        milanuncios: PreviewMilanuncios,
        coches_net: PreviewCochesNet,
        wallapop: PreviewWallapop,
        tiktok: PreviewTikTok,
        instagram: PreviewInstagram,
        facebook: PreviewFacebook,
    };
    return channelComponents[activeChannel.value] || PreviewMilanuncios;
}
</script>

<template>
    <Head :title="t('cars.ads_title', { brand: car.brand, model: car.model })" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ t('cars.ads_title', { brand: car.brand, model: car.model }) }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader :title="t('cars.car_full_name', { brand: car.brand, model: car.model, year: car.year })" :subtitle="`${formatCurrency(car.purchase_price)} · ${car.mileage} km`">
                    <template #actions>
                        <Link :href="route('cars.show', car.id)" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <ArrowLeftIcon class="h-4 w-4" />
                            {{ t('common.back') }}
                        </Link>
                        <a :href="route('cars.marketing.briefing', car.id)" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-600">
                            <DocumentIcon class="h-4 w-4" />
                            {{ t('cars.briefing_pdf') }}
                        </a>
                    </template>
                </PageHeader>

                <!-- Mensajes -->
                <div v-if="errorMsg" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                    {{ errorMsg }}
                </div>
                <div v-if="successMsg" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ successMsg }}
                </div>

                <!-- Channel Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="ch in CHANNELS"
                        :key="ch.key"
                        @click="activeChannel = ch.key"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition',
                            activeChannel === ch.key
                                ? 'bg-estoril-600 text-white shadow'
                                : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <span>{{ ch.icon }}</span>
                        {{ ch.label }}
                    </button>
                </div>

                <!-- Content Editor -->
                <div class="space-y-6">
                    <!-- Generate Button -->
                    <div class="flex items-center justify-between rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-900">Canal: {{ channelLabel(activeChannel) }}</h3>
                            <p class="text-xs text-gray-500">Genera contenido optimizado con IA para este canal</p>
                        </div>
                        <button
                            @click="generate"
                            :disabled="generating"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50"
                        >
                            <SparklesIcon v-if="generating" class="h-4 w-4 animate-spin" />
                            <SparklesIcon v-else class="h-4 w-4" />
                            {{ generating ? 'Generando…' : 'Generar con IA' }}
                        </button>
                        <button
                            @click="togglePreview"
                            class="ml-2 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                        >
                            <EyeIcon class="h-4 w-4" />
                            {{ showPreview ? 'Ocultar preview' : 'Ver preview' }}
                        </button>
                    </div>

                    <!-- Title -->
                    <FormSection :title="t('cars.ad_title')">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.title"
                                type="text"
                                :placeholder="t('cars.ad_title_placeholder')"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                            />
                            <button
                                v-if="form.title"
                                @click="copyToClipboard(form.title)"
                                class="rounded-lg bg-gray-100 p-2 text-gray-600 hover:bg-gray-200"
                                :title="t('cars.copy_title')"
                            >
                                <DocumentDuplicateIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </FormSection>

                    <!-- Description -->
                    <FormSection :title="t('cars.description')">
                        <textarea
                            v-model="form.description"
                            :placeholder="t('cars.ad_description_placeholder')"
                            rows="8"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                        ></textarea>
                        <div class="mt-2 flex justify-end">
                            <button
                                v-if="form.description"
                                @click="copyToClipboard(form.description)"
                                class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200"
                            >
                                <DocumentDuplicateIcon class="h-3 w-3" />
                                {{ t('cars.copy_description') }}
                            </button>
                        </div>
                    </FormSection>

                    <!-- Hashtags -->
                    <FormSection :title="t('cars.hashtags')">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in form.hashtags"
                                :key="tag"
                                class="inline-flex items-center gap-1 rounded-full bg-estoril-100 px-3 py-1 text-xs text-estoril-800"
                            >
                                #{{ tag }}
                                <button @click="removeHashtag(tag)" class="hover:text-estoril-600">
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <input
                                v-model="newHashtag"
                                @keyup.enter="addHashtag"
                                type="text"
                                :placeholder="t('cars.hashtag_placeholder')"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                            />
                            <button
                                @click="addHashtag"
                                class="rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                            >
                                {{ t('cars.add') }}
                            </button>
                        </div>
                    </FormSection>

                    <!-- Photo Tips -->
                    <FormSection :title="t('cars.photo_tips')">
                        <ul v-if="form.photo_tips.length" class="space-y-2">
                            <li
                                v-for="(tip, i) in form.photo_tips"
                                :key="i"
                                class="flex items-start gap-2 rounded-lg bg-amber-50 p-3"
                            >
                                <PhotoIcon class="h-4 w-4 flex-shrink-0 text-amber-600" />
                                <span class="flex-1 text-sm text-amber-900">{{ tip }}</span>
                                <button @click="removeTip(i)" class="text-amber-600 hover:text-amber-800">
                                    <XMarkIcon class="h-4 w-4" />
                                </button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400">{{ t('cars.ads_no_tips') }}</p>
                        <div class="mt-2 flex gap-2">
                            <input
                                v-model="newTip"
                                @keyup.enter="addTip"
                                type="text"
                                :placeholder="t('cars.photo_tip_placeholder')"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                            />
                            <button
                                @click="addTip"
                                class="rounded-lg bg-estoril-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-estoril-500"
                            >
                                {{ t('cars.add') }}
                            </button>
                        </div>
                    </FormSection>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                        <button
                            v-if="currentContent?.status === 'published'"
                            @click="publish"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-estoril-800"
                        >
                            <CheckCircleIcon class="h-4 w-4" />
                            Publicado
                        </button>
                        <button
                            v-else-if="currentContent"
                            @click="publish"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500"
                        >
                            <MegaphoneIcon class="h-4 w-4" />
                            Marcar como publicado
                        </button>
                        <button
                            @click="copyAll"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500"
                        >
                            <DocumentDuplicateIcon class="h-4 w-4" />
                            Copiar todo
                        </button>
                        <button
                            @click="save"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
                        >
                            <LightBulbIcon class="h-4 w-4" />
                            {{ saving ? 'Guardando…' : 'Guardar borrador' }}
                        </button>
                    </div>
                </div>

                <!-- Preview Section -->
                <div v-if="showPreview" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ t('cars.ads_preview') }}</h3>
                    <component
                        :is="renderPreview()"
                        :car="car"
                        :form="form"
                    />
                </div>

                <!-- History -->
                <FormSection :title="t('cars.ads_history')">
                    <div v-if="contents.length" class="space-y-3">
                        <div
                            v-for="content in contents"
                            :key="content.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-lg">{{ CHANNELS.find(c => c.key === content.channel)?.icon || '📄' }}</span>
                                <div>
                                    <div class="font-medium text-gray-900">{{ CHANNELS.find(c => c.key === content.channel)?.label || content.channel }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ content.status === 'published' ? t('cars.published') : t('cars.draft_label') }}
                                        · {{ new Date(content.updated_at).toLocaleDateString('es-ES') }}
                                    </div>
                                </div>
                            </div>
                            <Badge :variant="content.status === 'published' ? 'success' : 'warning'">
                                {{ content.status === 'published' ? t('cars.published') : t('cars.draft_label') }}
                            </Badge>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">{{ t('cars.ads_no_announcements') }}</p>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
