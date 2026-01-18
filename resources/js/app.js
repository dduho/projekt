import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import VueApexCharts from 'vue3-apexcharts';
import NotificationToast from './Components/NotificationToast.vue';

// Make route globally available
window.route = window.route || ((...args) => args.join('/'));

const appName = import.meta.env.VITE_APP_NAME || 'PRISM';
const pinia = createPinia();

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(VueApexCharts);
        
        app.mount(el);
        
        // Mount notification toast globally after main app
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
        const toastApp = createApp(NotificationToast);
        toastApp.use(pinia);
        toastApp.mount(toastContainer);
        
        return app;
    },
    progress: {
        color: '#667eea',
        showSpinner: true,
    },
});
