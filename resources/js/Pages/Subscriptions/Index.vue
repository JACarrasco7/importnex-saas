<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckIcon, CreditCardIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { useFormat } from '@/Composables/useFormat';

const props = defineProps({
    plans: Object,
    currentPlan: String,
    subscription: Object,
    on_trial: Boolean,
    trial_ends_at: String,
});

const { date, currency } = useFormat();
const planKeys = ['starter', 'pro', 'enterprise'];
</script>

<template>
    <Head title="Subscription" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subscription Plans</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Choose your plan" subtitle="Upgrade or downgrade your subscription at any time" />

                <div v-if="on_trial" class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center gap-3">
                        <SparklesIcon class="h-5 w-5 text-blue-600" />
                        <p class="text-sm text-blue-700"><strong>Trial active:</strong> ends {{ date(trial_ends_at) }}</p>
                    </div>
                </div>

                <div v-if="subscription" class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center gap-3">
                        <CheckIcon class="h-5 w-5 text-emerald-600" />
                        <p class="text-sm text-emerald-700">
                            <strong>Subscription active:</strong> {{ subscription.status }}
                            <span v-if="subscription.ends_at"> — ends {{ date(subscription.ends_at) }}</span>
                        </p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div v-for="key in planKeys" :key="key" :class="['overflow-hidden rounded-2xl bg-white shadow-sm ring-1', currentPlan === key ? 'ring-2 ring-indigo-500' : 'ring-gray-200']">
                        <div v-if="currentPlan === key" class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-3 text-center text-sm font-semibold text-white">
                            Current Plan
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2">
                                <CreditCardIcon class="h-5 w-5 text-gray-400" />
                                <h3 class="text-lg font-bold text-gray-900">{{ plans[key].name }}</h3>
                            </div>
                            <p class="mt-2 text-3xl font-bold text-gray-900">{{ currency(plans[key].price) }}<span class="text-sm font-normal text-gray-500">/month</span></p>
                            <p class="mt-2 text-sm text-gray-500">{{ plans[key].description }}</p>

                            <ul class="mt-6 space-y-2">
                                <li class="flex items-center gap-2 text-sm">
                                    <CheckIcon class="h-4 w-4 text-emerald-500" />
                                    <span class="text-gray-700">{{ plans[key].cars_limit }} cars</span>
                                </li>
                                <li class="flex items-center gap-2 text-sm">
                                    <CheckIcon class="h-4 w-4 text-emerald-500" />
                                    <span class="text-gray-700">{{ plans[key].clients_limit }} clients</span>
                                </li>
                            </ul>

                            <div class="mt-8">
                                <Link v-if="currentPlan === key" :href="route('subscriptions.show', key)" class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                    View details
                                </Link>
                                <form v-else-if="on_trial || !subscription" method="POST" :action="route('subscriptions.create', key)">
                                    <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                    <button type="submit" class="block w-full rounded-lg bg-indigo-600 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                        {{ on_trial ? 'Activate' : 'Subscribe' }}
                                    </button>
                                </form>
                                <form v-else method="POST" :action="route('subscriptions.swap', key)">
                                    <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                    <button type="submit" class="block w-full rounded-lg bg-purple-600 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-purple-500">
                                        Switch to {{ plans[key].name }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="subscription && !on_trial" class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Subscription actions</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage your recurring subscription</p>
                        </div>
                        <div class="flex gap-2">
                            <form v-if="subscription.status === 'active'" method="POST" :action="route('subscriptions.cancel')" class="inline">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="rounded-lg bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                    Cancel subscription
                                </button>
                            </form>
                            <form v-else method="POST" :action="route('subscriptions.resume')" class="inline">
                                <input type="hidden" name="_token" :value="$page.props.csrfToken" />
                                <button type="submit" class="rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                                    Resume subscription
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
