<template>
    <div class="w-full">
        <div v-if="label" class="flex items-center justify-between mb-2">
            <span :class="['text-sm', isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ label }}</span>
            <span :class="['text-sm font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ safePercentage }}%</span>
        </div>
        <div class="progress-bar">
            <div 
                class="progress-fill"
                :class="statusColorClass"
                :style="{ width: `${safePercentage}%` }"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const { isDarkText } = useTheme();

const props = defineProps({
    value: {
        type: Number,
        default: null
    },
    progress: {
        type: Number,
        default: null
    },
    max: {
        type: Number,
        default: 100
    },
    label: {
        type: String,
        default: ''
    },
    status: {
        type: String,
        default: 'Green'
    }
});

const safePercentage = computed(() => {
    const val = props.progress ?? props.value ?? 0;
    const result = Math.min(Math.round((val / props.max) * 100), 100);
    return isNaN(result) ? 0 : result;
});

const statusColorClass = computed(() => {
    const colors = {
        'Green': 'bg-emerald-500',
        'Amber': 'bg-amber-500',
        'Red': 'bg-red-500'
    };
    return colors[props.status] || 'bg-primary-500';
});
</script>
