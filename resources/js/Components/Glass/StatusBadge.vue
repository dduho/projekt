<template>
    <span 
        class="rag-badge"
        :class="statusClass"
    >
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['red', 'amber', 'green', 'gray', 'not_started', 'in_progress', 'completed', 'on_hold'].includes(value)
    },
    label: {
        type: String,
        default: null
    }
});

const statusClass = computed(() => {
    const statusMap = {
        'red': 'rag-red',
        'amber': 'rag-amber',
        'green': 'rag-green',
        'gray': 'rag-gray',
        'not_started': 'rag-gray',
        'in_progress': 'rag-amber',
        'completed': 'rag-green',
        'on_hold': 'rag-red'
    };
    return statusMap[props.status] || 'rag-gray';
});

const defaultLabel = computed(() => {
    const labelMap = {
        'red': 'RED',
        'amber': 'AMBER',
        'green': 'GREEN',
        'gray': 'GRAY',
        'not_started': 'Not Started',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'on_hold': 'On Hold'
    };
    return props.label || labelMap[props.status] || props.status.toUpperCase();
});
</script>
