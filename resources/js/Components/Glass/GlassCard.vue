<template>
    <div 
        class="glass-card"
        :class="{
            'glass-card-hover': hoverable,
            'animate-slide-up': animated
        }"
    >
        <!-- Header (optional) -->
        <div v-if="$slots.header || title" class="card-header">
            <slot name="header">
                <h3 class="card-title">{{ title }}</h3>
            </slot>
            <slot name="actions" />
        </div>

        <!-- Content -->
        <div :class="{ 'pt-6': !$slots.header && !title }">
            <slot />
        </div>

        <!-- Footer (optional) -->
        <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-white/10">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: ''
    },
    hoverable: {
        type: Boolean,
        default: false
    },
    animated: {
        type: Boolean,
        default: false
    }
});
</script>
