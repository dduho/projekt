import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const unreadCount = ref(0);

    const addNotification = (notification) => {
        const id = Date.now();
        notifications.value.push({
            id,
            type: notification.type || 'info', // success, error, warning, info
            title: notification.title || '',
            message: notification.message,
            duration: notification.duration || 5000,
            read: false
        });

        if (notification.duration) {
            setTimeout(() => {
                removeNotification(id);
            }, notification.duration);
        }

        updateUnreadCount();
    };

    const success = (message, title = 'Success') => {
        addNotification({ type: 'success', title, message });
    };

    const error = (message, title = 'Error') => {
        addNotification({ type: 'error', title, message, duration: 7000 });
    };

    const warning = (message, title = 'Warning') => {
        addNotification({ type: 'warning', title, message });
    };

    const info = (message, title = 'Info') => {
        addNotification({ type: 'info', title, message });
    };

    const removeNotification = (id) => {
        notifications.value = notifications.value.filter(n => n.id !== id);
        updateUnreadCount();
    };

    const markAsRead = (id) => {
        const notification = notifications.value.find(n => n.id === id);
        if (notification) {
            notification.read = true;
            updateUnreadCount();
        }
    };

    const markAllAsRead = () => {
        notifications.value.forEach(n => n.read = true);
        updateUnreadCount();
    };

    const clearAll = () => {
        notifications.value = [];
        unreadCount.value = 0;
    };

    const updateUnreadCount = () => {
        unreadCount.value = notifications.value.filter(n => !n.read).length;
    };

    return {
        notifications,
        unreadCount,
        addNotification,
        success,
        error,
        warning,
        info,
        removeNotification,
        markAsRead,
        markAllAsRead,
        clearAll
    };
});
