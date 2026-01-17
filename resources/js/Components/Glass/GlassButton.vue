<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="btn"
        :class="buttonClasses"
        @click="handleClick"
    >
        <span v-if="loading" class="spinner w-4 h-4" />
        <component v-else-if="icon" :is="icon" class="w-4 h-4" />
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button'
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'ghost', 'danger'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    icon: {
        type: Object,
        default: null
    },
    fullWidth: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click']);

const buttonClasses = computed(() => {
    const classes = [];
    
    // Variant
    switch (props.variant) {
        case 'primary':
            classes.push('btn-primary');
            break;
        case 'secondary':
            classes.push('btn-secondary');
            break;
        case 'ghost':
            classes.push('btn-ghost');
            break;
        case 'danger':
            classes.push('btn-danger');
            break;
    }
    
    // Size
    switch (props.size) {
        case 'sm':
            classes.push('text-sm px-3 py-1.5');
            break;
        case 'lg':
            classes.push('text-lg px-6 py-3');
            break;
    }
    
    // Full width
    if (props.fullWidth) {
        classes.push('w-full');
    }
    
    return classes;
});

const handleClick = (event) => {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
};
</script>
