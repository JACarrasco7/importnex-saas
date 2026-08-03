/**
 * Side-effect import: registers and immediately mounts the floating AI chat widget.
 * Imported from app.js, this file ensures Vite tree-shakes everything INSIDE this
 * module (Vue + AIChatWidget) into the `app-*.js` bundle.
 *
 * The widget is ONLY mounted when the user is authenticated. We detect this by
 * reading the Inertia page props (`page.props.auth.user`) which are shared by
 * HandleInertiaRequests. If no user is present (guest, public pages), we
 * silently skip mounting.
 */
import { createApp, h } from 'vue';
import AIChatWidget from './Components/AIChatWidget.vue';

function isAuthenticated() {
    if (typeof window === 'undefined') return false;

    try {
        const node = document.getElementById('app');
        if (node && node.dataset && node.dataset.page) {
            const page = JSON.parse(node.dataset.page);
            if (page?.props?.auth?.user) return true;
        }
    } catch (_) {
        // ignore parse errors
    }

    return false;
}

(function mountAiChat() {
    if (typeof window === 'undefined') return;

    const launch = () => {
        if (!isAuthenticated()) return;

        const host = document.createElement('div');
        host.id = 'ai-chat-launcher-root';
        document.body.appendChild(host);
        const app = createApp({ render: () => h(AIChatWidget) });
        app.mount(host);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', launch);
    } else {
        launch();
    }
})();
