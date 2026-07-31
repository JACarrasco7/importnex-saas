<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { SparklesIcon, XMarkIcon, PaperAirplaneIcon, TrashIcon, ArrowsPointingOutIcon, ArrowsPointingInIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const open = ref(false);
const expanded = ref(false);
const sending = ref(false);
const errorMsg = ref('');
const scroller = ref(null);

const props = defineProps({
    provider: { type: String, default: null },
    providerLabel: { type: String, default: 'AI' },
    model: { type: String, default: null },
    hasKey: { type: Boolean, default: false },
});

const messages = ref([
    { role: 'assistant', content: '¡Hola! Soy tu asistente IA. Pregúntame lo que necesites sobre tus coches, clientes o cualquier operativa.' },
]);

const providerText = computed(() => {
    if (!props.provider) return 'IA no configurada';
    let s = props.providerLabel || props.provider;
    if (props.model) s += ` · ${props.model}`;
    return s;
});

function scrollToBottom() {
    nextTick(() => {
        if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight;
    });
}

function clearAll() {
    messages.value = [{ role: 'assistant', content: 'Conversación reiniciada. ¿En qué te ayudo?' }];
    errorMsg.value = '';
}

async function send() {
    const lastUser = [...messages.value].reverse().find(m => m.role === 'user' && m.content.trim() !== '');
    if (!lastUser) return;
    if (!props.hasKey) {
        errorMsg.value = 'No hay IA configurada. Ve a Organización → Edit para añadir un proveedor y una API key.';
        return;
    }

    sending.value = true;
    errorMsg.value = '';
    const userText = lastUser.content;

    try {
        const resp = await axios.post(route('ai.chat.send'), {
            messages: messages.value
                .filter(m => m.role !== 'assistant' || m.content.trim() !== '')
                .map(m => ({ role: m.role, content: m.content })),
            max_tokens: 1500,
        });
        if (resp.data.success) {
            messages.value.push({ role: 'assistant', content: resp.data.text });
        } else {
            errorMsg.value = resp.data.error || 'La IA no pudo responder';
        }
    } catch (e) {
        errorMsg.value = e.response?.data?.error || e.response?.data?.message || e.message || 'Error desconocido';
    } finally {
        sending.value = false;
        scrollToBottom();
    }
}

function handleEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

const panelClass = computed(() =>
    expanded
        ? 'fixed inset-4 z-50 flex flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 sm:inset-8'
        : 'fixed bottom-24 right-6 z-50 flex h-[640px] w-[420px] max-w-[calc(100vw-2rem)] flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200',
);

onMounted(() => scrollToBottom());
</script>

<template>
    <!-- Floating button -->
    <button
        v-if="!open"
        @click="open = true"
        type="button"
        aria-label="Open AI chat"
        class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg ring-1 ring-black/5 transition hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <SparklesIcon class="h-6 w-6" />
        <span class="sr-only">AI chat</span>
    </button>

    <!-- Panel -->
    <div v-if="open" :class="panelClass">
        <!-- Header -->
        <header class="flex items-center justify-between gap-2 rounded-t-2xl border-b border-gray-200 bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-3 text-white">
            <div class="flex items-center gap-2 min-w-0">
                <SparklesIcon class="h-5 w-5 flex-shrink-0" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold leading-tight">Asistente IA</p>
                    <p class="truncate text-[11px] text-indigo-100">{{ providerText }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button @click="expanded = !expanded" type="button"
                        class="rounded p-1.5 text-white/80 hover:bg-white/10 hover:text-white"
                        :title="expanded ? 'Restaurar' : 'Expandir'">
                    <ArrowsPointingInIcon v-if="expanded" class="h-4 w-4" />
                    <ArrowsPointingOutIcon v-else class="h-4 w-4" />
                </button>
                <button @click="clearAll" type="button"
                        class="rounded p-1.5 text-white/80 hover:bg-white/10 hover:text-white" title="Reiniciar">
                    <TrashIcon class="h-4 w-4" />
                </button>
                <button @click="open = false" type="button"
                        class="rounded p-1.5 text-white/80 hover:bg-white/10 hover:text-white" title="Cerrar">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>
        </header>

        <!-- No provider warning -->
        <div v-if="!hasKey" class="m-3 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
            <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 flex-shrink-0" />
            <span>Necesitas configurar un proveedor IA en <strong>Organización → Edit</strong>.</span>
        </div>

        <!-- Error -->
        <div v-if="errorMsg" class="mx-3 mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 ring-1 ring-red-200">
            {{ errorMsg }}
        </div>

        <!-- Messages -->
        <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
            <div v-for="(m, idx) in messages" :key="idx"
                 :class="['flex', m.role === 'user' ? 'justify-end' : 'justify-start']">
                <div :class="[
                    'max-w-[85%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed',
                    m.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800'
                ]">
                    <pre class="whitespace-pre-wrap font-sans">{{ m.content }}</pre>
                </div>
            </div>
            <div v-if="sending" class="flex justify-start">
                <div class="rounded-2xl bg-gray-100 px-4 py-3 text-sm text-gray-500">
                    <span class="inline-flex gap-1">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0s"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.15s"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.3s"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Composer (Inertia v-model on last user message) -->
        <footer class="border-t border-gray-200 bg-white p-3 rounded-b-2xl">
            <div class="flex items-end gap-2">
                <textarea
                    v-model="messages[messages.length - 1].content"
                    @keydown="handleEnter"
                    rows="2"
                    :disabled="sending || !hasKey"
                    placeholder="Pregúntale a la IA… (Enter para enviar, Shift+Enter salto)"
                    class="block w-full resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50"></textarea>
                <button @click="send" :disabled="sending || !hasKey"
                        class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                    <PaperAirplaneIcon class="h-4 w-4" />
                </button>
            </div>
        </footer>
    </div>
</template>
