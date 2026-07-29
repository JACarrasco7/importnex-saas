<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" :class="['pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl p-4 shadow-lg ring-1', classes]">
            <div class="flex-shrink-0">
                <component :is="iconComponent" class="h-5 w-5" :class="iconClass" aria-hidden="true" />
            </div>
            <div class="flex-1 pt-0.5">
                <p v-if="title" class="text-sm font-semibold">{{ title }}</p>
                <p class="text-sm" :class="title ? 'text-gray-500' : 'text-gray-700'">
                    <slot>{{ message }}</slot>
                </p>
            </div>
            <button v-if="dismissible" type="button" class="flex-shrink-0 rounded-md p-1 text-gray-400 hover:text-gray-600" @click="$emit('close')">
                <XMarkIcon class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { CheckCircleIcon, XCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: true },
    type: { type: String, default: 'info' },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    dismissible: { type: Boolean, default: true },
});

defineEmits(['close']);

const variantMap = {
    success: { wrap: 'bg-emerald-50 ring-emerald-200 text-emerald-900', icon: 'text-emerald-500' },
    error: { wrap: 'bg-rose-50 ring-rose-200 text-rose-900', icon: 'text-rose-500' },
    warning: { wrap: 'bg-amber-50 ring-amber-200 text-amber-900', icon: 'text-amber-500' },
    info: { wrap: 'bg-blue-50 ring-blue-200 text-blue-900', icon: 'text-blue-500' },
};

const iconMap = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

const classes = computed(() => variantMap[props.type]?.wrap || variantMap.info.wrap);
const iconClass = computed(() => variantMap[props.type]?.icon || variantMap.info.icon);
const iconComponent = computed(() => iconMap[props.type] || iconMap.info);
</script>
