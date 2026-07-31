/**
 * Side-effect import: registers and immediately mounts the floating AI chat widget.
 * Imported from app.js, this file ensures Vite tree-shakes everything INSIDE this
 * module (Vue + AIChatWidget) into the `app-*.js` bundle.
 */
import { createApp, h } from 'vue';
import AIChatWidget from './Components/AIChatWidget.vue';

(function mountAiChat() {
    if (typeof window === 'undefined') return;
    const launch = () => {
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
