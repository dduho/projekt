import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const unreadCount = ref(0);
    const timers = ref(new Map());
    const pausedTimers = ref(new Map());

    const addNotification = (notification) => {
        const id = Date.now();
        const toast = {
            id,
            type: notification.type || 'info', // success, error, warning, info
            title: notification.title || '',
            message: notification.message,
            duration: notification.duration !== undefined ? notification.duration : 5000,
            read: false,
            progress: 100,
            startTime: Date.now(),
        };
        
        notifications.value.push(toast);

        // Auto-remove avec progress bar
        if (toast.duration > 0) {
            startTimer(id, toast.duration);
        }

        updateUnreadCount();
    };

    const startTimer = (id, duration) => {
        const startTime = Date.now();
        const notification = notifications.value.find(n => n.id === id);
        
        const updateProgress = () => {
            if (!notification) return;
            
            const elapsed = Date.now() - startTime;
            const remaining = duration - elapsed;
            notification.progress = Math.max(0, (remaining / duration) * 100);
            
            if (remaining <= 0) {
                removeNotification(id);
            } else {
                const timerId = requestAnimationFrame(updateProgress);
                timers.value.set(id, timerId);
            }
        };
        
        updateProgress();
    };

    const pauseNotification = (id) => {
        const timerId = timers.value.get(id);
        if (timerId) {
            cancelAnimationFrame(timerId);
            timers.value.delete(id);
            
            const notification = notifications.value.find(n => n.id === id);
            if (notification) {
                pausedTimers.value.set(id, {
                    progress: notification.progress,
                    duration: notification.duration,
                });
            }
        }
    };

    const resumeNotification = (id) => {
        const pausedData = pausedTimers.value.get(id);
        if (pausedData) {
            const remainingDuration = (pausedData.progress / 100) * pausedData.duration;
            startTimer(id, remainingDuration);
            pausedTimers.value.delete(id);
        }
    };

    const success = (message, title = '') => {
        addNotification({ type: 'success', title, message });
    };

    const error = (message, title = '') => {
        addNotification({ type: 'error', title, message, duration: 7000 });
    };

    const warning = (message, title = '') => {
        addNotification({ type: 'warning', title, message, duration: 6000 });
    };

    const info = (message, title = '') => {
        addNotification({ type: 'info', title, message });
    };

    const removeNotification = (id) => {
        // Clear timer
        const timerId = timers.value.get(id);
        if (timerId) {
            cancelAnimationFrame(timerId);
            timers.value.delete(id);
        }
        pausedTimers.value.delete(id);
        
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
        // Clear all timers
        timers.value.forEach(timerId => cancelAnimationFrame(timerId));
        timers.value.clear();
        pausedTimers.value.clear();
        
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
        pauseNotification,
        resumeNotification,
        markAsRead,
        markAllAsRead,
        clearAll
    };
});
