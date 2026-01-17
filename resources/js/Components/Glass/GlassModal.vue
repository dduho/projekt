<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Overlay -->
                <div 
                    class="modal-overlay"
                    @click="handleOverlayClick"
                />
                
                <!-- Modal Content -->
                <div class="min-h-screen px-4 text-center">
                    <!-- Centering trick -->
                    <span class="inline-block h-screen align-middle">&#8203;</span>
                    
                    <div 
                        class="inline-block align-middle glass-card max-w-2xl w-full text-left"
                        :class="sizeClasses"
                    >
                        <!-- Header -->
                        <div class="card-header">
                            <slot name="header">
                                <h3 class="card-title">{{ title }}</h3>
                            </slot>
                            <button 
                                v-if="closeable"
                                @click="close"
                                class="btn btn-ghost p-2"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <!-- Body -->
                        <div class="py-4">
                            <slot />
                        </div>
                        
                        <!-- Footer -->
                        <div v-if="$slots.footer" class="card-header mt-4 pt-4">
                            <slot name="footer" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    closeable: {
        type: Boolean,
        default: true
    },
    closeOnOverlay: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['close']);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'max-w-md';
        case 'lg':
            return 'max-w-4xl';
        case 'xl':
            return 'max-w-6xl';
        default:
            return 'max-w-2xl';
    }
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const handleOverlayClick = () => {
    if (props.closeOnOverlay) {
        close();
    }
};

// Prevent body scroll when modal is open
watch(() => props.show, (show) => {
    if (show) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
