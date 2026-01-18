<template>
  <div class="relative block min-w-0" @mouseenter="show = true" @mouseleave="show = false">
    <slot />
    <Transition name="tooltip">
      <div 
        v-if="show" 
        :class="[
          'absolute z-50 px-3 py-2 text-sm font-medium rounded-lg shadow-lg pointer-events-none',
          'backdrop-blur-xl border',
          isDarkText 
            ? 'bg-white/95 text-gray-900 border-gray-200' 
            : 'bg-gray-900/95 text-white border-white/20',
          positionClasses
        ]"
        style="white-space: nowrap; max-width: 300px; word-wrap: break-word; white-space: normal;"
      >
        {{ text }}
        <!-- Arrow -->
        <div 
          :class="[
            'absolute w-2 h-2 rotate-45',
            isDarkText ? 'bg-white/95 border-gray-200' : 'bg-gray-900/95 border-white/20',
            arrowClasses
          ]"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTheme } from '@/Composables/useTheme'

const { isDarkText } = useTheme()

const props = defineProps({
  text: {
    type: String,
    required: true
  },
  position: {
    type: String,
    default: 'top',
    validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
  }
})

const show = ref(false)

const positionClasses = computed(() => {
  const positions = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    right: 'left-full top-1/2 -translate-y-1/2 ml-2'
  }
  return positions[props.position] || positions.top
})

const arrowClasses = computed(() => {
  const arrows = {
    top: 'top-full left-1/2 -translate-x-1/2 -mt-1 border-b border-r',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 -mb-1 border-t border-l',
    left: 'left-full top-1/2 -translate-y-1/2 -ml-1 border-t border-r',
    right: 'right-full top-1/2 -translate-y-1/2 -mr-1 border-b border-l'
  }
  return arrows[props.position] || arrows.top
})
</script>

<style scoped>
.tooltip-enter-active,
.tooltip-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
