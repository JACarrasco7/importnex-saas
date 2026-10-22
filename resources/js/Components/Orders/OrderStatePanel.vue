<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';
import OrderCard from '@/Components/Orders/OrderCard.vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    order: { type: Object, required: true },
    transitions: { type: Array, required: true },
    replacementCars: { type: Array, default: () => [] },
});

const { t } = useTranslations();
const selected = ref(null);
const form = useForm({ to: '', reason: '', force: false });
const replaceForm = useForm({ car_id: '' });

const target = computed(() => props.transitions.find((tr) => tr.value === selected.value) || null);
const needsReason = computed(() => target.value?.needs_reason || form.force);

const choose = (transition) => {
    selected.value = transition.value;
    form.to = transition.value;
    form.force = false;
    form.reason = '';
};
const submit = () => {
    form.post(route('import-orders.transition', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { selected.value = null; form.reset(); },
    });
};
const replace = () => {
    replaceForm.post(route('import-orders.replace-vehicle', props.order.id), { preserveScroll: true });
};
</script>

<template>
    <OrderCard :title="t('orders.state.title')" :icon="ArrowsRightLeftIcon">
        <p class="text-xs text-gray-600 dark:text-gray-400">{{ t('orders.state.current', { state: order.state_label }) }} · {{ t('orders.state.client_sees', { label: order.client_state_label }) }}</p>

        <form v-if="order.state === 'searching_replacement'" class="mt-3 space-y-2 rounded-xl bg-amber-50 p-3 ring-1 ring-amber-200" @submit.prevent="replace">
            <label for="replacement-car" class="block text-xs font-semibold text-amber-900">{{ t('orders.state.replacement_label') }}</label>
            <select id="replacement-car" v-model="replaceForm.car_id" required class="block w-full rounded-lg border-amber-300 text-sm">
                <option value="" disabled>{{ t('orders.state.replacement_placeholder') }}</option>
                <option v-for="car in replacementCars" :key="car.id" :value="car.id">{{ car.label }}</option>
            </select>
            <p v-if="!replacementCars.length" class="text-xs text-amber-800">{{ t('orders.state.replacement_none') }}</p>
            <p v-if="replaceForm.errors.car_id || replaceForm.errors.general" class="text-xs text-rose-600">{{ replaceForm.errors.car_id || replaceForm.errors.general }}</p>
            <button type="submit" :disabled="replaceForm.processing || !replaceForm.car_id" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500 disabled:opacity-50">{{ t('orders.state.replace') }}</button>
        </form>

        <div v-if="transitions.length" class="mt-3 flex flex-wrap gap-2">
            <button
                v-for="transition in transitions"
                :key="transition.value"
                type="button"
                :class="['rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition', selected === transition.value
                    ? (transition.is_danger ? 'bg-rose-600 text-white ring-rose-600' : 'bg-estoril-700 text-white ring-estoril-700')
                    : (transition.is_danger ? 'bg-white text-rose-600 ring-rose-200 hover:bg-rose-50 dark:bg-asphalt-900' : 'bg-white text-gray-800 ring-gray-200 hover:bg-gray-50 dark:bg-asphalt-900 dark:text-gray-200 dark:ring-asphalt-600')]"
                @click="choose(transition)"
            >
                → {{ transition.label }}
            </button>
        </div>
        <p v-else class="mt-3 text-xs text-gray-500">{{ t('orders.state.none') }}</p>

        <form v-if="target" class="mt-3 space-y-2" @submit.prevent="submit">
            <label v-if="target.can_force" class="flex items-start gap-2 text-xs text-amber-800">
                <input v-model="form.force" type="checkbox" class="mt-0.5 rounded border-amber-300 text-amber-600" />
                {{ t('orders.state.force') }}
            </label>
            <textarea v-if="needsReason" v-model="form.reason" rows="2" required :placeholder="t('orders.state.reason')" :aria-label="t('orders.state.reason')" class="block w-full rounded-lg border-gray-300 text-sm dark:border-asphalt-600 dark:bg-asphalt-900 dark:text-gray-100" />
            <p v-for="(error, key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</p>
            <div class="flex gap-2">
                <button type="submit" :disabled="form.processing" :class="['rounded-lg px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50', target.is_danger ? 'bg-rose-600 hover:bg-rose-500' : 'bg-estoril-700 hover:bg-estoril-600']">
                    {{ t('orders.state.confirm', { state: target.label }) }}
                </button>
                <button type="button" class="text-xs font-semibold text-gray-500" @click="selected = null">{{ t('common.cancel') }}</button>
            </div>
        </form>
    </OrderCard>
</template>
