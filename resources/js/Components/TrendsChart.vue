<template>
  <div>
    <h3 :class="['text-lg font-semibold mb-6', textPrimary]">{{ t('Progress Trends') }}</h3>
    
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" :class="['p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400']">
      <AlertCircle class="w-4 h-4 inline mr-2" />
      {{ error }}
    </div>

    <!-- No Data State -->
    <div v-else-if="!trendData || trendData.length < 2" :class="['p-4 rounded-lg', bgSecondary, textMuted, 'text-center']">
      <TrendingDown class="w-12 h-12 mx-auto mb-3 opacity-30" />
      <p>{{ t('Not enough data to display trends') }}</p>
      <p class="text-sm mt-1">{{ t('Snapshots will be created daily') }}</p>
    </div>

    <!-- Chart Container -->
    <div v-else class="space-y-6">
      <!-- Completion Percentage Trend -->
      <div :class="['p-4 rounded-lg', bgSecondary]">
        <h4 :class="['text-sm font-semibold mb-4', textPrimary]">{{ t('Completion %') }}</h4>
        <canvas id="completionChart" ref="completionChartRef"></canvas>
      </div>

      <!-- RAG Status History -->
      <div :class="['p-4 rounded-lg', bgSecondary]">
        <h4 :class="['text-sm font-semibold mb-4', textPrimary]">{{ t('RAG Status History') }}</h4>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div
            v-for="(trend, index) in trendData"
            :key="`rag-${index}`"
            class="flex items-center justify-between py-2 px-3 rounded border border-gray-200 dark:border-gray-700"
          >
            <span :class="['text-sm', textMuted]">{{ formatDate(trend.date) }}</span>
            <span
              :class="[
                'px-3 py-1 rounded-full text-xs font-semibold',
                getRagStatusClass(trend.rag_status)
              ]"
            >
              {{ trend.rag_status }}
            </span>
          </div>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-1', textMuted]">{{ t('Starting %') }}</p>
          <p :class="['text-2xl font-bold text-blue-500']">
            {{ trendData[0]?.completion_percent?.toFixed(1) || '0' }}%
          </p>
        </div>
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-1', textMuted]">{{ t('Current %') }}</p>
          <p :class="['text-2xl font-bold text-green-500']">
            {{ trendData[trendData.length - 1]?.completion_percent?.toFixed(1) || '0' }}%
          </p>
        </div>
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-1', textMuted]">{{ t('Progress') }}</p>
          <p :class="['text-2xl font-bold', getProgressColor()]">
            {{ progressGain.toFixed(1) }}%
          </p>
        </div>
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-1', textMuted]">{{ t('Days Tracked') }}</p>
          <p :class="['text-2xl font-bold text-purple-500']">
            {{ trendData.length }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import { TrendingDown, AlertCircle } from 'lucide-vue-next'
import Chart from 'chart.js/auto'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const { isDark } = useTheme()
const { t, formatDate } = useTranslation()
const completionChartRef = ref(null)
let completionChart = null

const loading = ref(true)
const error = ref(null)
const trendData = ref([])

const bgSecondary = computed(() => isDark.value ? 'bg-gray-800/30' : 'bg-gray-50')
const textPrimary = computed(() => isDark.value ? 'text-white' : 'text-gray-900')
const textMuted = computed(() => isDark.value ? 'text-gray-400' : 'text-gray-600')

const progressGain = computed(() => {
  if (trendData.value.length < 2) return 0
  const first = trendData.value[0]?.completion_percent || 0
  const last = trendData.value[trendData.value.length - 1]?.completion_percent || 0
  return last - first
})

const getRagStatusClass = (status) => {
  const classes = {
    'Red': 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    'Amber': 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    'Green': 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
  }
  return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const getProgressColor = () => {
  const gain = progressGain.value
  if (gain >= 20) return 'text-green-500'
  if (gain >= 10) return 'text-blue-500'
  if (gain > 0) return 'text-amber-500'
  return 'text-red-500'
}

const fetchTrendData = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await fetch(`/api/projects/${props.projectId}/trends`)
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json()
    
    // Transform the data from ReportService format
    const trendArray = data.trends
    if (trendArray && trendArray.dates && trendArray.completion_percent) {
      // Map the parallel arrays into an array of objects
      trendData.value = trendArray.dates.map((date, index) => ({
        date,
        completion_percent: trendArray.completion_percent[index],
        rag_status: trendArray.rag_status[index]
      }))
    } else {
      trendData.value = []
    }

    // Render chart after data is loaded
    if (trendData.value.length >= 2) {
      await new Promise(resolve => setTimeout(resolve, 100))
      renderCompletionChart()
    }
  } catch (err) {
    error.value = t('Failed to load trend data')
    console.error('Trend fetch error:', err)
  } finally {
    loading.value = false
  }
}

const renderCompletionChart = () => {
  if (!completionChartRef.value) return

  // Destroy existing chart
  if (completionChart) {
    completionChart.destroy()
  }

  const ctx = completionChartRef.value.getContext('2d')
  const dates = trendData.value.map(d => {
    const date = new Date(d.date)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  })
  const percentages = trendData.value.map(d => d.completion_percent || 0)

  const borderColor = isDark.value ? 'rgba(59, 130, 246, 0.8)' : 'rgba(37, 99, 235, 0.8)'
  const backgroundColor = isDark.value ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)'
  const textColor = isDark.value ? '#e5e7eb' : '#374151'
  const gridColor = isDark.value ? 'rgba(75, 85, 99, 0.2)' : 'rgba(200, 200, 200, 0.2)'

  completionChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: dates,
      datasets: [
        {
          label: t('Completion %'),
          data: percentages,
          borderColor,
          backgroundColor,
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: borderColor,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        legend: {
          display: true,
          labels: {
            color: textColor,
            font: { size: 12 },
            usePointStyle: true,
            padding: 15
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: borderColor,
          borderWidth: 1,
          padding: 10,
          displayColors: true,
          callbacks: {
            label: function(context) {
              return context.parsed.y.toFixed(1) + '%'
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: {
            color: textColor,
            callback: function(value) {
              return value + '%'
            }
          },
          grid: {
            color: gridColor,
            drawBorder: false
          }
        },
        x: {
          ticks: {
            color: textColor,
            maxRotation: 45,
            minRotation: 0
          },
          grid: {
            display: false
          }
        }
      }
    }
  })
}

onMounted(() => {
  fetchTrendData()
})

watch(() => props.projectId, () => {
  fetchTrendData()
})

watch(() => isDark.value, () => {
  if (trendData.value.length >= 2) {
    renderCompletionChart()
  }
})
</script>
