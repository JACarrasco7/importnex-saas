import '../css/app.css';
import './bootstrap';
import './aiChatLauncher';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ToastContainer from '@/Components/ToastContainer.vue';
import CommandPalette from '@/Components/CommandPalette.vue';

const appName = import.meta.env.VITE_APP_NAME || 'JJ Import Motors';

createInertiaApp({
    title: (title) => `${title} · ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => [
                h(App, props),
                h(ToastContainer),
                h(CommandPalette),
            ],
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#1A306D',
        showSpinner: true,
    },
});
