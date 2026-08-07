<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="duration-200 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-150 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-200 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-150 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <div class="flex items-start gap-4">
                                <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', iconBg]">
                                    <component :is="iconComponent" class="h-6 w-6" :class="iconColor" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                        {{ title }}
                                    </DialogTitle>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            <slot>{{ message }}</slot>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-row-reverse gap-3">
                                <button
                                    type="button"
                                    :class="['inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2', confirmClass]"
                                    @click="$emit('confirm')"
                                >
                                    {{ confirmText }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                    @click="$emit('close')"
                                >
                                    {{ cancelText }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { ExclamationTriangleIcon, TrashIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    variant: { type: String, default: 'danger' },
});

defineEmits(['close', 'confirm']);

const variantMap = {
    danger: {
        icon: TrashIcon,
        bg: 'bg-rose-50',
        color: 'text-rose-600',
        btn: 'bg-rose-600 text-white hover:bg-rose-500 focus-visible:ring-rose-500',
    },
    warning: {
        icon: ExclamationTriangleIcon,
        bg: 'bg-amber-50',
        color: 'text-amber-600',
        btn: 'bg-amber-600 text-white hover:bg-amber-500 focus-visible:ring-amber-500',
    },
    info: {
        icon: CheckCircleIcon,
        bg: 'bg-blue-50',
        color: 'text-blue-600',
        btn: 'bg-blue-600 text-white hover:bg-blue-500 focus-visible:ring-blue-500',
    },
};

const iconComponent = computed(() => variantMap[props.variant].icon);
const iconBg = computed(() => variantMap[props.variant].bg);
const iconColor = computed(() => variantMap[props.variant].color);
const confirmClass = computed(() => variantMap[props.variant].btn);
</script>
