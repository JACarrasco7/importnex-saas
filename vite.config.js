import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    base: '/build/',
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules/vue') || id.includes('node_modules/@inertiajs/vue3')) {
                        return 'vendor-vue';
                    }
                    if (id.includes('node_modules/@inertiajs/core')) {
                        return 'vendor-inertia';
                    }
                    if (id.includes('node_modules/@heroicons')) {
                        return 'vendor-heroicons';
                    }
                    if (id.includes('node_modules/axios') || id.includes('node_modules/date-fns')) {
                        return 'vendor-utils';
                    }
                    if (id.includes('node_modules/@vueuse/core')) {
                        return 'vendor-ui';
                    }
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        }
    }
});
