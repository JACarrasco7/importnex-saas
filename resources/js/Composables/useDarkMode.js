import { ref, watch } from 'vue';

const STORAGE_KEY = 'jj-import-core-theme';

function getInitial() {
    if (typeof window === 'undefined') return 'light';
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const isDark = ref(getInitial() === 'dark');

function apply(value) {
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('dark', value);
}

apply(isDark.value);

watch(isDark, (value) => {
    apply(value);
    try {
        localStorage.setItem(STORAGE_KEY, value ? 'dark' : 'light');
    } catch (_) {
        // ignore storage errors (private mode, etc.)
    }
});

export function useDarkMode() {
    return {
        isDark,
        toggle: () => (isDark.value = !isDark.value),
        set: (value) => (isDark.value = value),
    };
}
