<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="toggleMenu"
      :class="[
        'flex items-center gap-2 px-3 py-2 rounded-lg transition-all cursor-pointer',
        isDarkText
          ? 'hover:bg-gray-200 text-gray-700'
          : 'hover:bg-white/10 text-white'
      ]"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
      </svg>
      <span class="text-sm font-medium uppercase">{{ currentLocale }}</span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="showMenu"
      :class="[
        'absolute right-0 mt-2 w-44 rounded-lg shadow-xl border z-[100]',
        'backdrop-blur-xl',
        isDarkText
          ? 'bg-white border-gray-200'
          : 'bg-gray-900/95 border-white/20'
      ]"
    >
      <button
        v-for="lang in languages"
        :key="lang.code"
        @click="switchLanguage(lang.code)"
        :class="[
          'w-full px-4 py-3 text-left flex items-center gap-3 transition-colors cursor-pointer',
          'first:rounded-t-lg last:rounded-b-lg',
          currentLocale === lang.code
            ? (isDarkText ? 'bg-indigo-50 text-indigo-600' : 'bg-indigo-500/20 text-indigo-300')
            : (isDarkText ? 'hover:bg-gray-100 text-gray-700' : 'hover:bg-white/5 text-gray-200')
        ]"
      >
        <span class="text-xl">{{ lang.flag }}</span>
        <div class="flex-1">
          <div class="font-medium text-sm">{{ lang.name }}</div>
        </div>
        <svg
          v-if="currentLocale === lang.code"
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'

const { isDarkText } = useTheme()
const page = usePage()

const showMenu = ref(false)
const dropdownRef = ref(null)

const languages = [
  { code: 'fr', name: 'Français', flag: '🇫🇷' },
  { code: 'en', name: 'English', flag: '🇬🇧' }
]

const currentLocale = computed(() => page.props.locale || 'fr')

const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

const switchLanguage = (locale) => {
  showMenu.value = false

  // Recharger la page avec le nouveau paramètre locale
  const url = new URL(window.location.href)
  url.searchParams.set('locale', locale)
  
  router.visit(url.pathname + url.search, {
    preserveState: false,
    preserveScroll: false,
  })
}

// Click outside handler
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
