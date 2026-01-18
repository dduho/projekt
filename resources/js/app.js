import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import VueApexCharts from 'vue3-apexcharts';
import NotificationToast from './Components/NotificationToast.vue';
import { useNotificationStore } from './stores/notification';

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

// Global Inertia event listeners for toast notifications
router.on('success', (event) => {
    const notificationStore = useNotificationStore();
    const page = event.detail.page;
    
    // Check for flash messages from Laravel
    if (page.props?.flash) {
        // Helper function to translate message if possible
        const translateMessage = (message) => {
            const translations = page.props.translations || {};
            return translations[message] || message;
        };
        
        if (page.props.flash.success) {
            const translated = translateMessage(page.props.flash.success);
            notificationStore.success(translated);
        }
        if (page.props.flash.error) {
            const translated = translateMessage(page.props.flash.error);
            notificationStore.error(translated);
        }
        if (page.props.flash.warning) {
            const translated = translateMessage(page.props.flash.warning);
            notificationStore.warning(translated);
        }
        if (page.props.flash.info) {
            const translated = translateMessage(page.props.flash.info);
            notificationStore.info(translated);
        }
        if (page.props.flash.message) {
            const translated = translateMessage(page.props.flash.message);
            notificationStore.info(translated);
        }
    }
});

router.on('error', (event) => {
    const notificationStore = useNotificationStore();
    const errors = event.detail.errors;
    
    // Display first error message
    if (errors && Object.keys(errors).length > 0) {
        const firstError = Object.values(errors)[0];
        notificationStore.error(Array.isArray(firstError) ? firstError[0] : firstError);
    }
});
