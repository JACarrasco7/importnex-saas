import '../css/app.css';
import './bootstrap';
import './aiChatLauncher';
import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ToastContainer from '@/Components/ToastContainer.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { i18nPlugin } from '@/i18n';

// N6: registrar service worker para Web Push
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js?v=4', { scope: '/' }).catch(() => {
            // silent — el push no es bloqueante
        });
    });
}

const rawAppName = import.meta.env.VITE_APP_NAME;
const appName = rawAppName && rawAppName !== 'Laravel' ? rawAppName : 'Importnex';

createInertiaApp({
    // Las webs públicas (marketplace, ficha, web de cada empresa) llevan el
    // nombre de la empresa en la pestaña, no el del producto (21-nov-2026).
    title: (title) => {
        let brandName = null;
        try {
            const props = usePage()?.props ?? {};
            brandName = props.marketplace?.orgName || props.orgBrand?.name || null;
        } catch {
            brandName = null;
        }
        return `${title} · ${brandName || appName}`;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => [
                h(App, props),
                h(ToastContainer),
                h(CommandPalette),
            ],
        })
            .use(plugin)
            .use(ZiggyVue);

        // Instalar i18n DESPUÉS de Inertia plugin (para tener usePage disponible).
        // Pasamos la primera página al install para sincronizar con backend.
        app.use(i18nPlugin, { initialPage: props.initialPage || null });

        return app.mount(el);
    },
    progress: {
        color: '#1A306D',
        showSpinner: true,
    },
});
