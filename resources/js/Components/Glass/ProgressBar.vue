<template>
    <div class="w-full">
        <div class="flex items-center justify-between mb-2">
            <span :class="['text-sm', isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ label }}</span>
            <span :class="['text-sm font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ percentage }}%</span>
        </div>
        <div class="progress-bar">
            <div 
                class="progress-fill"
                :style="{ width: `${percentage}%` }"
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
        required: true
    },
    max: {
        type: Number,
        default: 100
    },
    label: {
        type: String,
        default: ''
    }
});

const percentage = computed(() => {
    return Math.min(Math.round((props.value / props.max) * 100), 100);
});
</script>
