<template>
    <Teleport to="body">
        <div class="fixed bottom-6 right-6 z-[100] space-y-3 max-w-sm pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="toast-container pointer-events-auto"
                    @mouseenter="pauseTimer(notification.id)"
                    @mouseleave="resumeTimer(notification.id)"
                >
                    <!-- Glass morphism card with better visibility -->
                    <div 
                        class="relative overflow-hidden rounded-xl shadow-2xl backdrop-blur-2xl border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl"
                        :class="getCardClasses(notification.type)"
                    >
                        <!-- Solid background for better readability -->
                        <div class="absolute inset-0" :class="getBgClass(notification.type)"></div>
                        
                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 opacity-30" :class="getGradientClass(notification.type)"></div>
                        
                        <!-- Content -->
                        <div class="relative p-4 flex items-start gap-3">
                            <!-- Icon with animation -->
                            <div 
                                class="flex-shrink-0 rounded-lg p-2 animate-scale-in shadow-lg"
                                :class="getIconBgClass(notification.type)"
                            >
                                <component 
                                    :is="getIcon(notification.type)" 
                                    class="w-5 h-5 animate-bounce-subtle"
                                    :class="getIconColorClass(notification.type)"
                                />
                            </div>
                            
                            <!-- Text content -->
                            <div class="flex-1 min-w-0 pt-0.5">
                                <h4 
                                    v-if="notification.title" 
                                    class="text-sm font-bold mb-1 text-white drop-shadow-lg"
                                >
                                    {{ notification.title }}
                                </h4>
                                <p class="text-sm text-white font-medium leading-relaxed drop-shadow-md">
                                    {{ notification.message }}
                                </p>
                            </div>
                            
                            <!-- Close button -->
                            <button 
                                @click="removeNotification(notification.id)"
                                class="flex-shrink-0 text-white/80 hover:text-white transition-colors rounded-lg p-1 hover:bg-white/20"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        
                        <!-- Progress bar -->
                        <div 
                            v-if="notification.duration"
                            class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear"
                            :class="getProgressBarClass(notification.type)"
                            :style="{ width: `${notification.progress || 100}%` }"
                        ></div>
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

const getCardClasses = (type) => {
    const classes = {
        'success': 'border-green-400/60',
        'error': 'border-red-400/60',
        'warning': 'border-amber-400/60',
        'info': 'border-blue-400/60'
    };
    return classes[type] || classes.info;
};

const getBgClass = (type) => {
    const classes = {
        'success': 'bg-gradient-to-br from-green-600/90 to-emerald-700/90',
        'error': 'bg-gradient-to-br from-red-600/90 to-rose-700/90',
        'warning': 'bg-gradient-to-br from-amber-600/90 to-orange-700/90',
        'info': 'bg-gradient-to-br from-blue-600/90 to-indigo-700/90'
    };
    return classes[type] || classes.info;
};

const getGradientClass = (type) => {
    const classes = {
        'success': 'bg-gradient-to-br from-green-300 to-emerald-500',
        'error': 'bg-gradient-to-br from-red-300 to-rose-500',
        'warning': 'bg-gradient-to-br from-amber-300 to-orange-500',
        'info': 'bg-gradient-to-br from-blue-300 to-indigo-500'
    };
    return classes[type] || classes.info;
};

const getIconBgClass = (type) => {
    const classes = {
        'success': 'bg-white/25 border border-white/30',
        'error': 'bg-white/25 border border-white/30',
        'warning': 'bg-white/25 border border-white/30',
        'info': 'bg-white/25 border border-white/30'
    };
    return classes[type] || classes.info;
};

const getIconColorClass = (type) => {
    const classes = {
        'success': 'text-white',
        'error': 'text-white',
        'warning': 'text-white',
        'info': 'text-white'
    };
    return classes[type] || classes.info;
};

const getProgressBarClass = (type) => {
    const classes = {
        'success': 'bg-gradient-to-r from-green-500 to-emerald-600',
        'error': 'bg-gradient-to-r from-red-500 to-rose-600',
        'warning': 'bg-gradient-to-r from-amber-500 to-orange-600',
        'info': 'bg-gradient-to-r from-blue-500 to-indigo-600'
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

const pauseTimer = (id) => {
    notificationStore.pauseNotification(id);
};

const resumeTimer = (id) => {
    notificationStore.resumeNotification(id);
};
</script>

<style scoped>
/* Toast animations */
.toast-enter-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 1, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(120%) scale(0.8);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(120%) scale(0.7);
}

.toast-move {
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Icon animations */
@keyframes scale-in {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes bounce-subtle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-2px);
    }
}

.animate-scale-in {
    animation: scale-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.animate-bounce-subtle {
    animation: bounce-subtle 2s ease-in-out infinite;
}

/* Shadow enhancement */
.shadow-3xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}
</style>
