<template>
    <div class="w-full">
        <label v-if="label" :for="inputId" class="label-glass">
            {{ label }}
            <span v-if="required" class="text-red-400 ml-1">*</span>
        </label>
        
        <div class="relative">
            <div v-if="icon" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                <component :is="icon" class="w-5 h-5 text-gray-400" />
            </div>
            
            <input
                :id="inputId"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :class="inputClasses"
                @input="handleInput"
                @blur="emit('blur', $event)"
                @focus="emit('focus', $event)"
            />
            
            <div v-if="$slots.suffix" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <slot name="suffix" />
            </div>
        </div>
        
        <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-2 text-sm text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    icon: {
        type: Object,
        default: null
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

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`);

const inputClasses = computed(() => {
    const classes = ['input-glass'];
    
    if (props.icon) {
        classes.push('pl-10');
    }
    
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
