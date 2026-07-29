<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <div v-if="$slots.filters || $slots.default" class="flex flex-wrap items-center gap-3 border-b border-gray-200 bg-gray-50/60 px-4 py-3">
            <slot name="filters" />
            <div class="flex-1"></div>
            <slot name="actions" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <slot name="head" />
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <slot />
                </tbody>
            </table>
        </div>
        <div v-if="$slots.footer" class="border-t border-gray-200 bg-gray-50/60 px-4 py-3">
            <slot name="footer" />
        </div>
    </div>
</template>
