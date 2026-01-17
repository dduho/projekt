<template>
    <div class="w-full">
        <label v-if="label" :for="selectId" class="label-glass">
            {{ label }}
            <span v-if="required" class="text-red-400 ml-1">*</span>
        </label>
        
        <div class="relative">
            <div v-if="icon" class="absolute left-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                <component :is="icon" class="w-5 h-5 text-gray-400" />
            </div>
            
            <select
                :id="selectId"
                :value="modelValue"
                :disabled="disabled"
                :required="required"
                :class="selectClasses"
                @change="handleChange"
            >
                <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
                <option 
                    v-for="option in options" 
                    :key="option[valueKey]" 
                    :value="option[valueKey]"
                >
                    {{ option[labelKey] }}
                </option>
            </select>
            
            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                <ChevronDown class="w-5 h-5 text-gray-400" />
            </div>
        </div>
        
        <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-2 text-sm text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        required: true
    },
    valueKey: {
        type: String,
        default: 'value'
    },
    labelKey: {
        type: String,
        default: 'label'
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

const emit = defineEmits(['update:modelValue', 'change']);

const selectId = computed(() => `select-${Math.random().toString(36).substr(2, 9)}`);

const selectClasses = computed(() => {
    const classes = ['input-glass appearance-none pr-10'];
    
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

const handleChange = (event) => {
    const value = event.target.value;
    emit('update:modelValue', value);
    emit('change', value);
};
</script>
