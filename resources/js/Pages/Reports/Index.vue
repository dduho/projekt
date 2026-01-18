<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-10 animate-fadeIn">
        <h1 :class="['text-4xl font-bold mb-3 gradient-text']">{{ t('Reports') }}</h1>
        <p :class="[textMuted, 'text-lg']">{{ t('Generate and download reports for your projects') }}</p>
      </div>

      <!-- Portfolio Reports Section -->
      <div :class="['glass-card mb-8 animate-fadeInUp transition-all duration-300']">
        <h2 :class="['text-2xl font-bold mb-2', textPrimary]">{{ t('Portfolio Reports') }}</h2>
        <p :class="[textMuted, 'mb-6']">{{ t('Download portfolio-level summaries') }}</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- PDF Report -->
          <button
            @click="downloadPortfolioPdf"
            :disabled="loadingPdf"
            :class="[
              'glass-card-hover flex items-center gap-4 p-5 text-left',
              'disabled:opacity-50 disabled:cursor-not-allowed'
            ]"
          >
            <div :class="['flex-shrink-0 p-3 rounded-xl', isDarkText ? 'bg-red-100' : 'bg-red-500/20']">
              <svg class="w-6 h-6" :class="[isDarkText ? 'text-red-600' : 'text-red-400']" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" />
              </svg>
            </div>
            <div class="flex-1">
              <div :class="['font-semibold text-lg', textPrimary]">{{ t('Portfolio PDF') }}</div>
              <div :class="[textMuted, 'text-sm']">{{ t('Download PDF report') }}</div>
            </div>
            <div v-if="loadingPdf" class="flex-shrink-0">
              <div class="animate-spin text-lg">⟳</div>
            </div>
          </button>

          <!-- Excel Report -->
          <button
            @click="downloadPortfolioExcel"
            :disabled="loadingExcel"
            :class="[
              'glass-card-hover flex items-center gap-4 p-5 text-left',
              'disabled:opacity-50 disabled:cursor-not-allowed'
            ]"
          >
            <div :class="['flex-shrink-0 p-3 rounded-xl', isDarkText ? 'bg-green-100' : 'bg-green-500/20']">
              <svg class="w-6 h-6" :class="[isDarkText ? 'text-green-600' : 'text-green-400']" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zm10-1a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
              </svg>
            </div>
            <div class="flex-1">
              <div :class="['font-semibold text-lg', textPrimary]">{{ t('Portfolio Excel') }}</div>
              <div :class="[textMuted, 'text-sm']">{{ t('Download Excel report') }}</div>
            </div>
            <div v-if="loadingExcel" class="flex-shrink-0">
              <div class="animate-spin text-lg">⟳</div>
            </div>
          </button>
        </div>
      </div>

      <!-- Project Reports Section -->
      <div :class="['glass-card animate-fadeInUp']" style="animation-delay: 0.1s">
        <h2 :class="['text-2xl font-bold mb-2', textPrimary]">{{ t('Project Reports') }}</h2>
        <p :class="[textMuted, 'mb-6']">{{ t('Generate per-project exports and view details') }}</p>

        <div v-if="projects.length > 0" class="space-y-3">
          <div
            v-for="(project, idx) in projects"
            :key="project.id"
            :class="['glass-card-hover p-4 transition-all']"
            :style="{ 'animation-delay': `${0.15 + idx * 0.05}s` }"
            class="animate-fadeInUp"
          >
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 :class="['font-semibold text-lg', textPrimary]">{{ project.name }}</h3>
                <p :class="[textMuted, 'text-sm']">{{ project.project_code }}</p>
              </div>
              <div class="flex items-center gap-3">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                  project.rag_status === 'green' ? (isDarkText ? 'rag-green' : 'bg-green-500/30 text-green-300 border border-green-500/50') :
                  project.rag_status === 'amber' ? (isDarkText ? 'rag-amber' : 'bg-amber-500/30 text-amber-300 border border-amber-500/50') :
                  (isDarkText ? 'rag-red' : 'bg-red-500/30 text-red-300 border border-red-500/50')
                ]">
                  {{ project.rag_status.toUpperCase() }}
                </span>
                <div class="text-right">
                  <div :class="['text-sm font-bold', textPrimary]">{{ project.completion_percent }}%</div>
                  <div :class="['h-1 w-20 rounded-full', isDarkText ? 'bg-gray-200' : 'bg-white/10']">
                    <div
                      :class="['h-1 rounded-full transition-all', 
                        project.rag_status === 'green' ? 'bg-green-500' :
                        project.rag_status === 'amber' ? 'bg-amber-500' :
                        'bg-red-500'
                      ]"
                      :style="{ width: `${project.completion_percent}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex gap-2">
              <button
                @click="downloadProjectPdf(project)"
                :disabled="loadingPdfId === project.id"
                :class="[
                  'flex-1 px-3 py-2.5 rounded-lg font-medium transition-all',
                  isDarkText 
                    ? 'bg-red-500/20 hover:bg-red-500/30 text-red-600 border border-red-200' 
                    : 'bg-red-500/30 hover:bg-red-500/40 text-red-300 border border-red-500/40',
                  'disabled:opacity-50 disabled:cursor-not-allowed'
                ]"
              >
                {{ loadingPdfId === project.id ? '...' : 'PDF' }}
              </button>
              <button
                @click="downloadProjectExcel(project)"
                :disabled="loadingExcelId === project.id"
                :class="[
                  'flex-1 px-3 py-2.5 rounded-lg font-medium transition-all',
                  isDarkText 
                    ? 'bg-green-500/20 hover:bg-green-500/30 text-green-600 border border-green-200' 
                    : 'bg-green-500/30 hover:bg-green-500/40 text-green-300 border border-green-500/40',
                  'disabled:opacity-50 disabled:cursor-not-allowed'
                ]"
              >
                {{ loadingExcelId === project.id ? '...' : 'Excel' }}
              </button>
              <button
                @click="viewProjectDetails(project)"
                :class="[
                  'flex-1 px-3 py-2.5 rounded-lg font-medium transition-all',
                  isDarkText 
                    ? 'bg-prism-500/20 hover:bg-prism-500/30 text-prism-600 border border-prism-200' 
                    : 'glass hover:bg-white/10 text-white border border-white/20',
                ]"
              >
                {{ t('View') }}
              </button>
            </div>
          </div>
        </div>

        <div v-else :class="['text-center py-12', textMuted]">
          <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ t('No projects available') }}
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  projects: {
    type: Array,
    required: true,
  },
})

const page = usePage()
const { isDarkText } = useTheme()
const { t } = useTranslation()

const loadingPdf = ref(false)
const loadingExcel = ref(false)
const loadingPdfId = ref(null)
const loadingExcelId = ref(null)

const textPrimary = computed(() => isDarkText.value ? 'text-gray-900' : 'text-white')
const textMuted = computed(() => isDarkText.value ? 'text-gray-500' : 'text-slate-400')

const downloadPortfolioPdf = async () => {
  loadingPdf.value = true
  try {
    window.location.href = '/reports/portfolio/pdf'
  } finally {
    loadingPdf.value = false
  }
}

const downloadPortfolioExcel = async () => {
  loadingExcel.value = true
  try {
    window.location.href = '/reports/portfolio/excel'
  } finally {
    loadingExcel.value = false
  }
}

const downloadProjectPdf = async (project) => {
  loadingPdfId.value = project.id
  try {
    window.location.href = `/reports/projects/${project.id}/pdf`
  } finally {
    loadingPdfId.value = null
  }
}

const downloadProjectExcel = async (project) => {
  loadingExcelId.value = project.id
  try {
    window.location.href = `/reports/projects/${project.id}/excel`
  } finally {
    loadingExcelId.value = null
  }
}

const viewProjectDetails = (project) => {
  window.location.href = route('projects.show', project.id)
}
</script>
