<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    title: { type: String, required: true },
    icon: { type: Object, default: null },
    items: { type: Array, required: true },
    openByDefault: { type: Boolean, default: false },
});

const open = ref(props.openByDefault ?? false);

const toggle = () => {
    open.value = !open.value;
};

const isActive = (item) => {
    if (item.route && route().current(item.route)) return true;
    if (item.route && route().current(item.route + '*')) return true;
    return false;
};

const hasActiveChild = computed(() => {
    return props.items.some((item) => isActive(item));
});
</script>

<template>
    <div class="mb-1">
        <button
            @click="toggle"
            :class="[
                'group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                hasActiveChild ? 'bg-estoril-50/50 text-estoril-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
            ]"
        >
            <component v-if="icon" :is="icon" :class="['h-5 w-5 flex-shrink-0', hasActiveChild ? 'text-estoril-600' : 'text-gray-400']" />
            <span class="flex-1 text-left text-sm">{{ title }}</span>
            <ChevronRightIcon :class="['h-4 w-4 transition-transform', open ? 'rotate-90' : '']" />
        </button>

        <div v-show="open" class="mt-1 space-y-0.5 pl-3">
            <a
                v-for="item in items.filter((i) => i.external)"
                :key="'ext-' + item.route"
                :href="route(item.route)"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-3 rounded-lg px-3 py-1.5 text-sm font-semibold text-estoril-700 transition hover:bg-estoril-50"
            >
                <component :is="item.icon" class="h-4 w-4 flex-shrink-0 text-estoril-600" />
                <span class="flex-1">{{ item.name }}</span>
                <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 text-estoril-500" />
            </a>
            <Link
                v-for="item in items.filter((i) => !i.external)"
                :key="'int-' + item.route + item.name"
                :href="item.param ? route(item.route, item.param) : route(item.route)"
                :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-1.5 text-sm transition',
                    isActive(item) ? 'bg-estoril-50 text-estoril-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                ]"
            >
                <component :is="item.icon" :class="['h-4 w-4 flex-shrink-0', isActive(item) ? 'text-estoril-600' : 'text-gray-400']" />
                <span class="flex-1">{{ item.name }}</span>
                <span
                    v-if="item.badge && item.badge > 0"
                    :class="['inline-flex items-center justify-center rounded-full px-1.5 py-0 text-xs font-semibold text-white', item.badgeColor || 'bg-rose-500']"
                >
                    {{ item.badge }}
                </span>
            </Link>
        </div>
    </div>
</template>
