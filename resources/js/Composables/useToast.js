import { useNotificationStore } from '@/stores/notification';

export function useToast() {
    const notificationStore = useNotificationStore();

    const toast = {
        success: (message, title = '') => {
            notificationStore.success(message, title);
        },

        error: (message, title = '') => {
            notificationStore.error(message, title);
        },

        warning: (message, title = '') => {
            notificationStore.warning(message, title);
        },

        info: (message, title = '') => {
            notificationStore.info(message, title);
        },

        custom: (options) => {
            notificationStore.addNotification(options);
        },
    };

    return { toast };
}
