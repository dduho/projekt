<template>
  <div class="flex gap-2 mb-4">
    <button
      v-for="lang in languages"
      :key="lang.code"
      type="button"
      @click="$emit('update:modelValue', lang.code)"
      :class="[
        'px-4 py-2 rounded-lg font-medium transition-all',
        modelValue === lang.code
          ? 'bg-blue-500 text-white shadow-lg'
          : isDarkText
            ? 'bg-white/80 text-gray-700 hover:bg-white'
            : 'bg-white/10 text-white hover:bg-white/20'
      ]"
    >
      {{ lang.flag }} {{ lang.label }}
    </button>
  </div>
</template>

<script setup>
import { useTheme } from '@/Composables/useTheme'

const { isDarkText } = useTheme()

defineProps({
  modelValue: {
    type: String,
    default: 'fr'
  }
})

defineEmits(['update:modelValue'])

const languages = [
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'en', label: 'English', flag: '🇬🇧' }
]
</script>
