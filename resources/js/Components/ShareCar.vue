<template>
    <div ref="rootRef" class="relative inline-block" data-test="share-car">
        <button
            type="button"
            @click="toggle"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-estoril-500 dark:border-asphalt-700 dark:bg-asphalt-800 dark:text-gray-200 dark:hover:bg-asphalt-700"
            :aria-expanded="open"
            :aria-haspopup="true"
            :aria-label="t('share.label', 'Compartir este coche')"
        >
            <ShareIcon class="h-4 w-4" />
            {{ t('share.label', 'Compartir') }}
            <ChevronDownIcon class="h-3 w-3 transition-transform" :class="open ? 'rotate-180' : ''" />
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-95 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 z-20 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-asphalt-800 dark:ring-asphalt-700"
                role="menu"
            >
                <div class="py-1">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-asphalt-700"
                        role="menuitem"
                        @click="open = false"
                    >
                        <ChatBubbleBottomCenterIcon class="h-4 w-4 text-green-600" />
                        {{ t('share.whatsapp', 'WhatsApp') }}
                    </a>
                    <a
                        :href="emailUrl"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-asphalt-700"
                        role="menuitem"
                        @click="open = false"
                    >
                        <EnvelopeIcon class="h-4 w-4 text-estoril-600" />
                        {{ t('share.email', 'Email') }}
                    </a>
                    <a
                        :href="twitterUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-asphalt-700"
                        role="menuitem"
                        @click="open = false"
                    >
                        <ShareIcon class="h-4 w-4 text-sky-500" />
                        {{ t('share.twitter', 'Twitter / X') }}
                    </a>
                    <button
                        type="button"
                        @click="copyLink"
                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-asphalt-700"
                        role="menuitem"
                    >
                        <component
                            :is="copied ? CheckIcon : LinkIcon"
                            class="h-4 w-4"
                            :class="copied ? 'text-emerald-600' : 'text-gray-500'"
                        />
                        <span>{{ copied ? t('share.copied', 'Enlace copiado') : t('share.copy', 'Copiar enlace') }}</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onClickOutside } from '@vueuse/core';
import {
    ShareIcon,
    ChevronDownIcon,
    ChatBubbleBottomCenterIcon,
    EnvelopeIcon,
    LinkIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    car: { type: Object, required: true },
    whatsappNumber: { type: String, default: '34600000000' },
});

const { t } = useTranslations();
const open = ref(false);
const copied = ref(false);
const rootRef = ref(null);

onClickOutside(rootRef, () => {
    open.value = false;
});

function toggle() {
    open.value = !open.value;
}

const carUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    return `${window.location.origin}${window.location.pathname}`;
});

const carTitle = computed(
    () => `${props.car?.brand ?? ''} ${props.car?.model ?? ''} ${props.car?.year ?? ''}`.trim()
);

const shareMessage = computed(() => {
    const price = props.car?.purchase_price
        ? new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(props.car.purchase_price)
        : '';
    const mileage = props.car?.mileage ? `${(props.car.mileage / 1000).toFixed(0)}k km` : '';
    return [
        t('share.whatsapp_intro', 'Hola! Me interesa este coche:'),
        '',
        carTitle.value,
        mileage ? `· ${mileage}` : '',
        price ? `· ${price}` : '',
        '',
        carUrl.value,
    ]
        .filter(Boolean)
        .join('\n');
});

const whatsappUrl = computed(
    () => `https://wa.me/${props.whatsappNumber}?text=${encodeURIComponent(shareMessage.value)}`
);
const emailUrl = computed(
    () => `mailto:?subject=${encodeURIComponent(carTitle.value)}&body=${encodeURIComponent(shareMessage.value)}`
);
const twitterUrl = computed(
    () => `https://twitter.com/intent/tweet?text=${encodeURIComponent(`${carTitle.value} ${carUrl.value}`)}`
);

async function copyLink() {
    try {
        await navigator.clipboard.writeText(carUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
        open.value = false;
    } catch {
        window.prompt(t('share.copy_prompt', 'Copia este enlace'), carUrl.value);
        open.value = false;
    }
}
</script>
