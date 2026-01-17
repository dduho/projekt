import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import VueApexCharts from 'vue3-apexcharts';
import NotificationToast from './Components/NotificationToast.vue';

const appName = import.meta.env.VITE_APP_NAME || 'PRISM';
const pinia = createPinia();

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(VueApexCharts)
            .component('NotificationToast', NotificationToast);
        
        // Mount notification toast globally
        const toastContainer = document.createElement('div');
        document.body.appendChild(toastContainer);
        createApp(NotificationToast).use(pinia).mount(toastContainer);
        
        return app.mount(el);
    },
    progress: {
        color: '#667eea',
        showSpinner: true,
    },
});
