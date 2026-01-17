<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-50 space-y-2 max-w-md">
            <TransitionGroup name="toast">
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="toast"
                    :class="toastClass(notification.type)"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <component :is="getIcon(notification.type)" class="w-5 h-5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 v-if="notification.title" class="text-sm font-semibold mb-1">
                                {{ notification.title }}
                            </h4>
                            <p class="text-sm">{{ notification.message }}</p>
                        </div>
                        <button 
                            @click="removeNotification(notification.id)"
                            class="flex-shrink-0 text-gray-400 hover:text-white transition-colors"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const notificationStore = useNotificationStore();
const notifications = computed(() => notificationStore.notifications);

const toastClass = (type) => {
    const classes = {
        'success': 'border-l-4 border-green-500',
        'error': 'border-l-4 border-red-500',
        'warning': 'border-l-4 border-amber-500',
        'info': 'border-l-4 border-prism-500'
    };
    return classes[type] || classes.info;
};

const getIcon = (type) => {
    const icons = {
        'success': CheckCircle,
        'error': XCircle,
        'warning': AlertTriangle,
        'info': Info
    };
    return icons[type] || Info;
};

const removeNotification = (id) => {
    notificationStore.removeNotification(id);
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.95);
}
</style>
