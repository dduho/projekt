<template>
    <div class="w-full">
        <label v-if="label" :for="textareaId" class="label-glass">
            {{ label }}
            <span v-if="required" class="text-red-400 ml-1">*</span>
        </label>
        
        <textarea
            :id="textareaId"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            class="input-glass resize-none"
            :class="textareaClasses"
            @input="handleInput"
        />
        
        <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-2 text-sm text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    rows: {
        type: Number,
        default: 4
    },
    error: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const textareaId = computed(() => `textarea-${Math.random().toString(36).substr(2, 9)}`);

const textareaClasses = computed(() => {
    const classes = [];
    
    if (props.error) {
        classes.push('border-red-500 focus:ring-red-500');
    }
    
    if (props.disabled) {
        classes.push('opacity-50 cursor-not-allowed');
    }
    
    return classes;
});

const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>
