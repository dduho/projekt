<template>
  <div>
    <h3 :class="['text-lg font-semibold mb-6', textPrimary]">{{ t('Completion Forecast') }}</h3>
    
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
    <div v-else-if="!forecast || Object.keys(forecast).length === 0" :class="['p-4 rounded-lg', bgSecondary, textMuted, 'text-center']">
      <Zap class="w-12 h-12 mx-auto mb-3 opacity-30" />
      <p>{{ t('Not enough data to generate forecast') }}</p>
      <p class="text-sm mt-1">{{ t('Minimum 2 snapshots required') }}</p>
    </div>

    <!-- Forecast Content -->
    <div v-else class="space-y-6">
      <!-- Main Forecast Card -->
      <div
        :class="[
          'p-6 rounded-lg border-2 transition-all',
          forecast.at_risk
            ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700'
            : 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700'
        ]"
      >
        <div class="flex items-start justify-between mb-4">
          <div>
            <p :class="['text-sm font-semibold', textMuted]">{{ t('Predicted Completion') }}</p>
            <p :class="['text-3xl font-bold mt-2', forecast.at_risk ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400']">
              {{ formatDate(forecast.predicted_date) }}
            </p>
          </div>
          <div
            :class="[
              'p-3 rounded-lg',
              forecast.at_risk
                ? 'bg-red-100 dark:bg-red-900/40'
                : 'bg-blue-100 dark:bg-blue-900/40'
            ]"
          >
            <component
              :is="forecast.at_risk ? AlertTriangle : TrendingUp"
              :class="[
                'w-8 h-8',
                forecast.at_risk ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'
              ]"
            />
          </div>
        </div>

        <div v-if="forecast.at_risk" class="text-sm text-red-600 dark:text-red-400">
          <p class="font-semibold mb-1">⚠️ {{ t('Project at Risk') }}</p>
          <p>{{ t('At current velocity, the project will not meet its target date') }}</p>
        </div>
      </div>

      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Current Completion -->
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-2', textMuted]">{{ t('Current Completion') }}</p>
          <div class="flex items-end gap-1">
            <p :class="['text-2xl font-bold text-blue-500']">{{ forecast.current_percent.toFixed(1) }}</p>
            <p :class="['text-xs mb-1', textMuted]">%</p>
          </div>
          <ProgressBar :value="forecast.current_percent" class="mt-2" />
        </div>

        <!-- Velocity -->
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-2', textMuted]">{{ t('Velocity') }}</p>
          <div class="flex items-end gap-1">
            <p :class="['text-2xl font-bold', getVelocityColor()]">{{ forecast.velocity.toFixed(2) }}</p>
            <p :class="['text-xs mb-1', textMuted]">%/day</p>
          </div>
          <p :class="['text-xs mt-2', textMuted]">
            {{ forecast.days_remaining }} {{ t('days') }} {{ t('remaining') }}
          </p>
        </div>

        <!-- Target Date -->
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-2', textMuted]">{{ t('Target Date') }}</p>
          <p :class="['text-sm font-semibold', getDaysBufferColor()]">
            {{ formatDate(forecast.target_date) }}
          </p>
          <p
            :class="[
              'text-xs mt-2 font-semibold',
              forecast.days_buffer > 0 ? 'text-green-500' : 'text-red-500'
            ]"
          >
            {{ forecast.days_buffer > 0 ? '+' : '' }}{{ forecast.days_buffer }} {{ t('days') }}
          </p>
        </div>

        <!-- Confidence -->
        <div :class="['p-4 rounded-lg', bgSecondary]">
          <p :class="['text-xs mb-2', textMuted]">{{ t('Confidence') }}</p>
          <p
            :class="[
              'text-2xl font-bold',
              forecast.confidence_level === 'High'
                ? 'text-green-500'
                : forecast.confidence_level === 'Medium'
                ? 'text-amber-500'
                : 'text-orange-500'
            ]"
          >
            {{ forecast.confidence_level }}
          </p>
          <p :class="['text-xs mt-2', textMuted]">
            {{ forecast.data_points }} {{ t('snapshots') }}
          </p>
        </div>
      </div>

      <!-- Trend Analysis -->
      <div :class="['p-4 rounded-lg', bgSecondary]">
        <h4 :class="['text-sm font-semibold mb-4', textPrimary]">{{ t('Analysis') }}</h4>
        <p :class="['text-sm leading-relaxed', textMuted]">
          {{ forecast.analysis_message }}
        </p>
      </div>

      <!-- Timeline Comparison -->
      <div :class="['p-4 rounded-lg', bgSecondary]">
        <h4 :class="['text-sm font-semibold mb-4', textPrimary]">{{ t('Timeline Comparison') }}</h4>
        <div class="space-y-3">
          <!-- Target Timeline -->
          <div>
            <div class="flex justify-between mb-2">
              <p :class="['text-sm', textPrimary]">{{ t('Target') }}</p>
              <span :class="['text-xs font-semibold', 'text-blue-500']">
                {{ daysUntilTarget }} {{ t('days away') }}
              </span>
            </div>
            <ProgressBar :value="targetProgress" class="h-2" />
          </div>

          <!-- Predicted Timeline -->
          <div>
            <div class="flex justify-between mb-2">
              <p :class="['text-sm', textPrimary]">{{ t('Predicted') }}</p>
              <span :class="['text-xs font-semibold', getPredictedColor()]">
                {{ daysUntilPredicted }} {{ daysUntilPredicted > 0 ? t('days away') : t('days overdue') }}
              </span>
            </div>
            <ProgressBar
              :value="predictedProgress"
              :color="forecast.at_risk ? 'red' : 'green'"
              class="h-2"
            />
          </div>
        </div>
      </div>

      <!-- Details Table -->
      <div :class="['p-4 rounded-lg overflow-x-auto', bgSecondary]">
        <h4 :class="['text-sm font-semibold mb-4', textPrimary]">{{ t('Details') }}</h4>
        <table :class="['w-full text-sm']">
          <tbody class="space-y-2">
            <tr :class="['border-b', 'border-gray-200 dark:border-gray-700']">
              <td :class="[textMuted, 'py-2']">{{ t('Data Points') }}</td>
              <td :class="[textPrimary, 'text-right font-semibold']">{{ forecast.data_points }}</td>
            </tr>
            <tr :class="['border-b', 'border-gray-200 dark:border-gray-700']">
              <td :class="[textMuted, 'py-2']">{{ t('Average Velocity') }}</td>
              <td :class="[textPrimary, 'text-right font-semibold']">{{ forecast.velocity.toFixed(2) }}%/{{ t('day') }}</td>
            </tr>
            <tr :class="['border-b', 'border-gray-200 dark:border-gray-700']">
              <td :class="[textMuted, 'py-2']">{{ t('Remaining Work') }}</td>
              <td :class="[textPrimary, 'text-right font-semibold']">{{ (100 - forecast.current_percent).toFixed(1) }}%</td>
            </tr>
            <tr>
              <td :class="[textMuted, 'py-2']">{{ t('Status') }}</td>
              <td :class="['text-right']">
                <span
                  :class="[
                    'px-2 py-1 rounded text-xs font-semibold',
                    forecast.at_risk
                      ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300'
                      : 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                  ]"
                >
                  {{ forecast.at_risk ? t('At Risk') : t('On Track') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import { TrendingUp, AlertTriangle, AlertCircle, Zap } from 'lucide-vue-next'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const { isDark } = useTheme()
const { t, formatDate } = useTranslation()

const loading = ref(true)
const error = ref(null)
const forecast = ref(null)

const bgSecondary = computed(() => isDark.value ? 'bg-gray-800/30' : 'bg-gray-50')
const textPrimary = computed(() => isDark.value ? 'text-white' : 'text-gray-900')
const textMuted = computed(() => isDark.value ? 'text-gray-400' : 'text-gray-600')

const daysUntilTarget = computed(() => {
  if (!forecast.value) return 0
  const target = new Date(forecast.value.target_date)
  const today = new Date()
  return Math.max(0, Math.ceil((target - today) / (1000 * 60 * 60 * 24)))
})

const daysUntilPredicted = computed(() => {
  if (!forecast.value) return 0
  const predicted = new Date(forecast.value.predicted_date)
  const today = new Date()
  return Math.ceil((predicted - today) / (1000 * 60 * 60 * 24))
})

const targetProgress = computed(() => {
  if (!forecast.value) return 0
  const start = new Date(forecast.value.project_start_date)
  const target = new Date(forecast.value.target_date)
  const today = new Date()
  const totalDays = (target - start) / (1000 * 60 * 60 * 24)
  const elapsedDays = (today - start) / (1000 * 60 * 60 * 24)
  return Math.min(100, Math.max(0, (elapsedDays / totalDays) * 100))
})

const predictedProgress = computed(() => {
  if (!forecast.value) return 0
  const start = new Date(forecast.value.project_start_date)
  const predicted = new Date(forecast.value.predicted_date)
  const today = new Date()
  const totalDays = (predicted - start) / (1000 * 60 * 60 * 24)
  const elapsedDays = (today - start) / (1000 * 60 * 60 * 24)
  return Math.min(100, Math.max(0, (elapsedDays / totalDays) * 100))
})

const getVelocityColor = () => {
  if (!forecast.value) return 'text-gray-500'
  const velocity = forecast.value.velocity
  if (velocity >= 3) return 'text-green-500'
  if (velocity >= 1) return 'text-blue-500'
  if (velocity > 0) return 'text-amber-500'
  return 'text-red-500'
}

const getDaysBufferColor = () => {
  if (!forecast.value) return textPrimary.value
  return forecast.value.days_buffer > 0
    ? 'text-green-600 dark:text-green-400'
    : 'text-red-600 dark:text-red-400'
}

const getPredictedColor = () => {
  if (!forecast.value) return 'text-gray-500'
  return forecast.value.at_risk ? 'text-red-600' : 'text-green-600'
}

const fetchForecast = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await fetch(`/api/projects/${props.projectId}/trends`)
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json()
    forecast.value = data.forecast || {}

    if (!forecast.value || Object.keys(forecast.value).length === 0) {
      forecast.value = {}
    }
  } catch (err) {
    error.value = t('Failed to load forecast data')
    console.error('Forecast fetch error:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchForecast()
})

watch(() => props.projectId, () => {
  fetchForecast()
})
</script>
