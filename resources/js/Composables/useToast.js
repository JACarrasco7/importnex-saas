import { ref, readonly } from 'vue';

const toasts = ref([]);
let counter = 0;

function push(message, opts = {}) {
    const id = ++counter;
    const toast = {
        id,
        message,
        type: opts.type ?? 'info',
        title: opts.title ?? null,
        duration: opts.duration ?? 4000,
        dismissible: opts.dismissible ?? true,
    };
    toasts.value.push(toast);
    if (toast.duration > 0) {
        setTimeout(() => dismiss(id), toast.duration);
    }
    return id;
}

function dismiss(id) {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) toasts.value.splice(idx, 1);
}

function clear() {
    toasts.value.splice(0, toasts.value.length);
}

export function useToast() {
    return {
        toasts: readonly(toasts),
        success: (msg, opts) => push(msg, { ...opts, type: 'success' }),
        error: (msg, opts) => push(msg, { ...opts, type: 'error', duration: (opts?.duration ?? 6000) }),
        warning: (msg, opts) => push(msg, { ...opts, type: 'warning' }),
        info: (msg, opts) => push(msg, { ...opts, type: 'info' }),
        dismiss,
        clear,
    };
}
