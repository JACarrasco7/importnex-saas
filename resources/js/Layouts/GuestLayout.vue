<script setup>
import { Link } from '@inertiajs/vue3';
import { TruckIcon } from '@heroicons/vue/24/outline';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div class="flex min-h-screen bg-linear-to-br from-slate-50 via-white to-estoril-50">
        <!-- Left side: marketing -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between bg-linear-to-br from-estoril-700 to-estoril-900 p-12 text-white">
            <Link href="/" class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 backdrop-blur">
                    <TruckIcon class="h-5 w-5 text-white" />
                </div>
                <span class="text-xl font-bold">Importnex</span>
            </Link>

            <div>
                <h2 class="text-4xl font-bold leading-tight">Run your car import business on autopilot.</h2>
                <p class="mt-4 text-lg text-estoril-100">Inventory, clients, finance, AI verification — all in one platform.</p>
                <div class="mt-8 grid grid-cols-3 gap-6">
                    <div>
                        <p class="text-3xl font-bold">80%</p>
                        <p class="mt-1 text-sm text-estoril-100">less time on spreadsheets</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold">3x</p>
                        <p class="mt-1 text-sm text-estoril-100">faster turnover</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold">24/7</p>
                        <p class="mt-1 text-sm text-estoril-100">AI assistance</p>
                    </div>
                </div>
            </div>

            <p class="text-sm text-estoril-100">© {{ new Date().getFullYear() }} Importnex</p>
        </div>

        <!-- Right side: form -->
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 sm:px-12 lg:px-20">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <Link href="/" class="mb-8 inline-flex items-center gap-2 lg:hidden">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-estoril-600">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-xl font-bold text-gray-900">Importnex</span>
                </Link>
                <div class="rounded-2xl bg-white px-8 py-10 shadow-xl ring-1 ring-gray-200">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
