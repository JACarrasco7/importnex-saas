<script setup>
import { ChatBubbleBottomCenterIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/Composables/useTranslations';
import { useBrand } from '@/Composables/useBrand';

const { t } = useTranslations();
const { brand, waNumber, whatsappUrl } = useBrand();
</script>

<template>
    <a
        v-if="waNumber"
        :href="whatsappUrl(`Hola ${brand.name}, me interesa más información`)"
        target="_blank"
        rel="noopener noreferrer"
        class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-green-600 text-white shadow-2xl transition-all hover:bg-green-700 hover:scale-110 hover:-translate-y-1"
        :aria-label="t('public.whatsapp_label', {}, 'Contactar por WhatsApp')"
    >
        <ChatBubbleBottomCenterIcon class="h-7 w-7" />
    </a>
</template>
