<template>
    <span 
        class="rag-badge"
        :class="statusClass"
    >
        {{ displayLabel }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'gray'
    },
    label: {
        type: String,
        default: null
    }
});

// Normaliser le status en minuscule pour la comparaison
const normalizedStatus = computed(() => {
    if (!props.status) return 'gray';
    return props.status.toLowerCase();
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
    return statusMap[normalizedStatus.value] || 'rag-gray';
});

const displayLabel = computed(() => {
    if (props.label) return props.label;
    
    const labelMap = {
        'red': 'Red',
        'amber': 'Amber',
        'green': 'Green',
        'gray': 'N/A',
        'not_started': 'Not Started',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'on_hold': 'On Hold'
    };
    return labelMap[normalizedStatus.value] || props.status || 'N/A';
});
</script>
