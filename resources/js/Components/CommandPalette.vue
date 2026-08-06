<template>
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[90] flex items-start justify-center bg-asphalt-900/50 backdrop-blur-sm pt-[10vh] px-4"
            @click.self="close"
        >
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 -translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95 -translate-y-2"
            >
                <div
                    v-if="open"
                    class="card-premium w-full max-w-xl overflow-hidden shadow-2xl ring-1 ring-asphalt-900/10 dark:ring-white/10"
                    role="dialog"
                    aria-label="Buscador global"
                >
                    <div class="flex items-center gap-3 border-b border-asphalt-200 px-4 dark:border-asphalt-700">
                        <MagnifyingGlassIcon class="h-5 w-5 text-asphalt-400" aria-hidden="true" />
                        <input
                            ref="inputRef"
                            v-model="query"
                            type="search"
                            placeholder="Buscar páginas, coches, clientes..."
                            class="flex-1 border-0 bg-transparent py-3 text-base text-asphalt-900 placeholder:text-asphalt-400 focus:outline-none focus:ring-0 dark:text-asphalt-50"
                            @keydown.down.prevent="move(1)"
                            @keydown.up.prevent="move(-1)"
                            @keydown.enter.prevent="selectActive"
                            @keydown.escape="close"
                        />
                        <kbd class="hidden sm:inline-flex items-center gap-1 rounded border border-asphalt-200 bg-asphalt-50 px-1.5 py-0.5 text-[10px] font-medium text-asphalt-500 dark:border-asphalt-700 dark:bg-asphalt-800 dark:text-asphalt-400">
                            ESC
                        </kbd>
                    </div>

                    <div class="max-h-80 overflow-y-auto p-2">
                        <p v-if="!results.length" class="px-3 py-6 text-center text-sm text-asphalt-500">
                            Sin resultados para "{{ query }}"
                        </p>

                        <ul v-else class="space-y-1">
                            <li v-for="(item, i) in results" :key="item.id ?? item.href">
                                <button
                                    type="button"
                                    @click="go(item)"
                                    @mouseenter="activeIndex = i"
                                    :class="[
                                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition',
                                        activeIndex === i
                                            ? 'bg-estoril-50 text-estoril-700 dark:bg-estoril-700/30 dark:text-estoril-200'
                                            : 'text-asphalt-700 hover:bg-asphalt-50 dark:text-asphalt-200 dark:hover:bg-asphalt-700/50',
                                    ]"
                                >
                                    <component :is="item.icon || Squares2X2Icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span class="flex-1 truncate">{{ item.label }}</span>
                                    <span v-if="item.hint" class="text-xs text-asphalt-400">{{ item.hint }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t border-asphalt-200 bg-asphalt-50 px-4 py-2 text-[11px] text-asphalt-500 dark:border-asphalt-700 dark:bg-asphalt-800/50 dark:text-asphalt-400">
                        <kbd class="font-mono">↑↓</kbd> navegar · <kbd class="font-mono">↵</kbd> abrir · <kbd class="font-mono">esc</kbd> cerrar
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const inputRef = ref(null);

const page = usePage();
const navItems = computed(() => {
    const menus = page.props?.menus ?? [];
    const out = [];
    const flatten = (items, parent = '') => {
        items.forEach((item) => {
            out.push({
                id: item.key ?? item.href,
                label: item.label ?? item.text,
                href: item.href,
                hint: parent || undefined,
                icon: item.icon,
            });
            if (item.children?.length) flatten(item.children, item.label ?? item.text);
        });
    };
    flatten(menus);
    return out;
});

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return navItems.value.slice(0, 8);
    return navItems.value
        .filter((i) => i.label?.toLowerCase().includes(q) || i.href?.toLowerCase().includes(q))
        .slice(0, 12);
});

function close() {
    open.value = false;
    query.value = '';
    activeIndex.value = 0;
}

function move(delta) {
    if (!results.value.length) return;
    const next = activeIndex.value + delta;
    activeIndex.value = (next + results.value.length) % results.value.length;
}

function selectActive() {
    const item = results.value[activeIndex.value];
    if (item) go(item);
}

function go(item) {
    if (!item?.href) return;
    close();
    router.visit(item.href);
}

function handleShortcut(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open.value = !open.value;
        if (open.value) nextTick(() => inputRef.value?.focus());
    }
}

watch(open, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
        nextTick(() => inputRef.value?.focus());
    } else {
        document.body.style.overflow = '';
    }
});

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleShortcut);
}
</script>
