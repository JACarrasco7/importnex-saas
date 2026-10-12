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
    EyeIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormSection from '@/Components/FormSection.vue';
import { useFormat } from '@/Composables/useFormat';
import PreviewMilanuncios from '@/Components/PreviewMilanuncios.vue';
import PreviewCochesNet from '@/Components/PreviewCochesNet.vue';
import PreviewWallapop from '@/Components/PreviewWallapop.vue';
import PreviewTikTok from '@/Components/PreviewTikTok.vue';
import PreviewInstagram from '@/Components/PreviewInstagram.vue';
import PreviewFacebook from '@/Components/PreviewFacebook.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    car: Object,
    contents: Array,
    adFooter: { type: String, default: '' },
    limits: { type: Object, default: () => ({}) },
});

const { currency: formatCurrency } = useFormat();
const { t } = useTranslations();

const CHANNELS = [
    { key: 'milanuncios', label: 'Milanuncios', icon: '🛒', type: 'portal' },
    { key: 'coches_net', label: 'Coches.net', icon: '🚗', type: 'portal' },
    { key: 'wallapop', label: 'Wallapop', icon: '📱', type: 'portal' },
    { key: 'tiktok', label: 'TikTok', icon: '🎵', type: 'social' },
    { key: 'instagram', label: 'Instagram', icon: '📷', type: 'social' },
    { key: 'facebook', label: 'Facebook', icon: '📘', type: 'social' },
];

// B1 auditoría 09-sep-2026: los límites por canal viven en
// config/marketing_limits.php y llegan al front vía props.limits.
// El fallback local es defensivo (no debería usarse si el backend envía props).
const FALLBACK_GUIDE = {
    titleMax: 100, descMax: 2200, hashtagsMax: 5, tip: '',
};

function guideFor(channel) {
    const data = props.limits && props.limits[channel];
    if (data) {
        return {
            titleMax: data.title_max ?? FALLBACK_GUIDE.titleMax,
            descMax: data.desc_max ?? FALLBACK_GUIDE.descMax,
            hashtagsMax: data.max_hashtags ?? FALLBACK_GUIDE.hashtagsMax,
            tip: data.help ?? '',
        };
    }
    return FALLBACK_GUIDE;
}
const activeGuide = computed(() => guideFor(activeChannel.value));

function counterClass(len, max) {
    if (!max) return 'text-gray-400';
    const ratio = len / max;
    if (ratio > 1) return 'text-red-600 font-semibold';
    if (ratio > 0.9) return 'text-amber-600';
    return 'text-gray-400';
}

const activeChannel = ref('milanuncios');
const activeSlot = ref(1);          // para redes sociales: 1..3 (post/story #N)
const activeKind = ref('post');      // para redes sociales: 'post' | 'story'
const generating = ref(false);
const saving = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const showPreview = ref(false);

const SOCIAL_SLOTS = 3; // 3 posts + 3 stories por canal
const PORTAL_CHANNELS = new Set(['milanuncios', 'coches_net', 'wallapop']);
const isSocial = computed(() => ['tiktok', 'instagram', 'facebook'].includes(activeChannel.value));
const isPortal = computed(() => PORTAL_CHANNELS.has(activeChannel.value));

// Facebook es híbrido: tiene posts/stories (social) + ficha de Marketplace (ad).
// TikTok/Instagram solo posts/stories. Los portales puros solo ficha (ad).
const socialKinds = computed(() => {
    if (activeChannel.value === 'facebook') {
        return [
            { value: 'post', label: 'Publicaciones' },
            { value: 'story', label: 'Stories' },
            { value: 'ad', label: 'Marketplace' },
        ];
    }
    return [
        { value: 'post', label: 'Publicaciones' },
        { value: 'story', label: 'Stories' },
    ];
});

// Contenido agrupado por (channel, kind, slot) para acceso rápido
const contentMap = computed(() => {
    const map = new Map();
    for (const c of props.contents || []) {
        const key = `${c.channel}|${c.kind || (isPortal.value ? 'ad' : 'post')}|${c.slot || 1}`;
        map.set(key, c);
    }
    return map;
});

// Contenido de la combinación activa (channel + kind + slot para redes; channel solo para portales)
const currentContent = computed(() => {
    const ch = activeChannel.value;
    if (isSocial.value) {
        return contentMap.value.get(`${ch}|${activeKind.value}|${activeSlot.value}`) || null;
    }
    return contentMap.value.get(`${ch}|ad|1`) || null;
});

// Editable form fields (se sincronizan al cambiar de tab / kind / slot)
const form = ref({
    title: '',
    description: '',
    hashtags: [],
    photo_tips: [],
    subir_pasos: '',
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
            subir_pasos: currentContent.value.subir_pasos || '',
        };
    } else {
        form.value = {
            title: '',
            description: '',
            hashtags: [],
            photo_tips: [],
            subir_pasos: '',
        };
    }
}

watch([activeChannel, activeKind, activeSlot], loadContent);

// Re-sincroniza el formulario cuando cambian los contenidos (tras generar/guardar).
// Vue re-evalúa currentContent al mutar props.contents, pero loadContent solo
// se dispara al cambiar de canal; con este watch el form se refresca en sitio.
watch(
    () => props.contents.map(c => c.id + ':' + c.updated_at).join('|'),
    () => loadContent(),
);
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
    // El endpoint save actualiza la fila (car_id, channel, kind, slot) del contenido activo.
    saving.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    router.post(
        route('cars.marketing.save', props.car.id),
        {
            channel: activeChannel.value,
            kind: isSocial.value ? activeKind.value : 'ad',
            slot: isSocial.value ? activeSlot.value : 1,
            title: form.value.title,
            description: form.value.description,
            hashtags: form.value.hashtags,
            photo_tips: form.value.photo_tips,
            subir_pasos: form.value.subir_pasos,
        },
        {
            preserveScroll: true,
            onFinish: () => { saving.value = false; },
            onError: (e) => { errorMsg.value = e?.message || 'Error guardando.'; },
            onSuccess: () => {
                successMsg.value = 'Guardado.';
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

// Texto listo para pegar tal cual en la plataforma: sin etiquetas ("Título:",
// "Hashtags:"...), solo el contenido real en el orden en que se publica +
// el pie de contacto común de la empresa al final.
function copyAll() {
    const parts = [];
    if (form.value.title) parts.push(form.value.title);
    if (form.value.description) parts.push(form.value.description);
    if (form.value.hashtags.length) parts.push(form.value.hashtags.map(t => `#${t}`).join(' '));
    if (props.adFooter) parts.push(props.adFooter);
    const text = parts.join('\n\n');
    navigator.clipboard.writeText(text);
    successMsg.value = 'Todo copiado al portapapeles, listo para pegar.';
}

function channelLabel(channel) {
    return CHANNELS.find(c => c.key === channel)?.label || channel;
}

// Piezas del canal activo: total y publicadas (para el contador del tab).
function channelStats(channel) {
    const rows = props.contents.filter(c => c.channel === channel);
    return {
        total: rows.length,
        published: rows.filter(c => c.status === 'published').length,
    };
}

function hasContent(channel) {
    return channelStats(channel).total > 0;
}

function contentStatus(channel) {
    const rows = props.contents.filter(c => c.channel === channel);
    if (rows.length && rows.every(c => c.status === 'published')) return 'published';
    if (rows.some(c => c.status === 'published')) return 'partial';
    return rows.length ? 'draft' : null;
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
                        <span
                            v-if="hasContent(ch.key)"
                            class="ml-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none"
                            :class="contentStatus(ch.key) === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            :title="contentStatus(ch.key) === 'published' ? 'Listo' : (contentStatus(ch.key) === 'partial' ? 'Parcial' : 'Borrador')"
                        >{{ channelStats(ch.key).total }}</span>
                    </button>
                </div>

                <!-- v2: Kind tabs (solo redes sociales) — Posts / Stories / Marketplace -->
                <div v-if="isSocial" class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg bg-gray-100 p-1">
                        <button
                            v-for="k in socialKinds"
                            :key="k.value"
                            @click="activeKind = k.value"
                            :class="[
                                'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                activeKind === k.value ? 'bg-white text-estoril-700 shadow' : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            {{ k.label }}
                        </button>
                    </div>
                    <!-- Slot selector (solo post/story) -->
                    <div v-if="activeKind !== 'ad'" class="flex gap-1">
                        <button
                            v-for="n in SOCIAL_SLOTS"
                            :key="n"
                            @click="activeSlot = n"
                            :class="[
                                'h-7 w-7 rounded-full text-xs font-semibold transition',
                                activeSlot === n ? 'bg-estoril-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50'
                            ]"
                            :title="activeKind === 'post' ? `Publicación ${n} de 3` : `Story ${n} de 3`"
                        >
                            {{ n }}
                        </button>
                    </div>
                    <span v-if="currentContent" class="text-xs text-gray-400">
                        {{ activeKind === 'ad' ? 'Ficha Marketplace' : `${activeKind === 'post' ? 'Publicación' : 'Story'} ${activeSlot}/3` }}
                        · {{ channelStats(activeChannel).published }}/{{ channelStats(activeChannel).total }} listas en {{ channelLabel(activeChannel) }}
                    </span>
                    <span v-else class="text-xs text-gray-400">Sin contenido para esta pieza — genéralo con IA o impórtalo del ZIP</span>
                </div>

                <!-- Content Editor -->
                <div class="space-y-6">
                    <!-- Generate Button -->
                    <div class="flex items-center justify-between rounded-xl bg-white p-4 ring-1 ring-gray-200">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900">{{ t('marketing.channel_label', { channel: channelLabel(activeChannel) }) }}</h3>
                                <span v-if="currentContent?.source === 'zip'" class="rounded-full bg-estoril-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-estoril-800" :title="t('marketing.from_zip_help')">
                                    {{ t('marketing.from_zip') }}
                                </span>
                                <span v-else-if="currentContent?.source === 'ai'" class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-800" :title="t('marketing.from_ai_help')">
                                    {{ t('marketing.from_ai') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">{{ t('marketing.generate_help') }}</p>
                        </div>
                        <button
                            @click="generate"
                            :disabled="generating"
                            class="inline-flex items-center gap-2 rounded-lg bg-estoril-600 px-4 py-2 text-sm font-semibold text-white hover:bg-estoril-500 disabled:opacity-50"
                        >
                            <SparklesIcon v-if="generating" class="h-4 w-4 animate-spin" />
                            <SparklesIcon v-else class="h-4 w-4" />
                            {{ generating ? t('marketing.generating') : t('marketing.generate_ai') }}
                        </button>
                        <button
                            @click="togglePreview"
                            class="ml-2 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                        >
                            <EyeIcon class="h-4 w-4" />
                            {{ showPreview ? t('marketing.hide_preview') : t('marketing.show_preview') }}
                        </button>
                    </div>

                    <!-- Consejo de la plataforma activa -->
                    <div v-if="activeGuide.tip" class="flex items-start gap-2 rounded-lg bg-blue-50 p-3 text-xs text-blue-900 ring-1 ring-blue-100">
                        <LightBulbIcon class="h-4 w-4 shrink-0 text-blue-500" />
                        <span>{{ activeGuide.tip }}</span>
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
                        <div class="mt-1 text-right text-xs" :class="counterClass(form.title.length, activeGuide.titleMax)">
                            {{ form.title.length }} / {{ activeGuide.titleMax }}
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
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs" :class="counterClass(form.description.length, activeGuide.descMax)">
                                {{ form.description.length }} / {{ activeGuide.descMax }} caracteres
                            </span>
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
                        <div v-if="activeGuide.hashtagsMax > 0" class="mt-1 text-right text-xs" :class="counterClass(form.hashtags.length, activeGuide.hashtagsMax)">
                            {{ form.hashtags.length }} / {{ activeGuide.hashtagsMax }} recomendados
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
                                <PhotoIcon class="h-4 w-4 shrink-0 text-amber-600" />
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
                            {{ saving ? t('cars.saving') : t('marketing.save_draft') }}
                        </button>
                    </div>
                    <p v-if="adFooter" class="text-right text-xs text-gray-400">
                        "Copiar todo" añade automáticamente: <span class="font-medium text-gray-500">{{ adFooter }}</span>
                    </p>
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

                <!-- v2: Pasos para subir (instrucciones del ZIP / IA por pieza) -->
                <FormSection v-if="form.subir_pasos" title="Pasos para subir">
                    <div class="rounded-lg bg-estoril-50 p-4 ring-1 ring-estoril-100">
                        <pre class="whitespace-pre-wrap font-sans text-sm text-estoril-900">{{ form.subir_pasos }}</pre>
                        <div class="mt-2 flex justify-end">
                            <button
                                @click="copyToClipboard(form.subir_pasos)"
                                class="inline-flex items-center gap-1 rounded-lg bg-white px-2 py-1 text-xs text-estoril-700 ring-1 ring-estoril-200 hover:bg-estoril-100"
                            >
                                <DocumentDuplicateIcon class="h-3 w-3" />
                                Copiar pasos
                            </button>
                        </div>
                    </div>
                    <textarea
                        v-model="form.subir_pasos"
                        rows="3"
                        placeholder="Instrucciones para subir esta pieza al canal…"
                        class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-estoril-500 focus:ring-estoril-500"
                    ></textarea>
                </FormSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
