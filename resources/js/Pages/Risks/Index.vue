<template>
  <AppLayout :page-title="t('Risks')" :page-description="t('Identify, assess and mitigate project risks')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']">{{ t('Risks Management') }}</h1>
          <p :class="[isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ t('Identify, assess and mitigate project risks') }}</p>
        </div>
        <div class="flex gap-2">
          <GlassButton
            variant="secondary"
            @click="$inertia.visit(route('risks.matrix'))"
          >
            <Grid class="w-5 h-5 mr-2" />
            {{ t('Risk Matrix') }}
          </GlassButton>
          <GlassButton
            variant="primary"
            @click="$inertia.visit(route('risks.create'))"
            v-if="can('create risks')"
          >
            <Plus class="w-5 h-5 mr-2" />
            {{ t('New Risk') }}
          </GlassButton>
        </div>
      </div>

      <!-- Filters -->
      <GlassCard>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <GlassInput
            v-model="filters.search"
            :placeholder="t('Search risks...')"
            @input="debouncedSearch"
          />
          <GlassSelect
            v-model="filters.status"
            :options="statusOptions"
            :placeholder="t('All Statuses')"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.project"
            :options="projectOptions"
            :placeholder="t('All Projects')"
            @change="applyFilters"
          />
          <GlassButton variant="secondary" @click="resetFilters">
            <X class="w-4 h-4 mr-2" />
            {{ t('Reset') }}
          </GlassButton>
        </div>
      </GlassCard>

      <!-- Risk Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <GlassCard>
          <div class="text-center">
            <p :class="['text-4xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ stats.total }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Total Risks') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-red-400 mb-2">{{ stats.critical }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Critical') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-orange-400 mb-2">{{ stats.high }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('High') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-amber-400 mb-2">{{ stats.medium }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Medium') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-purple-400 mb-2">{{ stats.auto_generated || 0 }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">ML</p>
          </div>
        </GlassCard>
      </div>

      <!-- Projects Risk Dashboard -->
      <GlassCard>
        <div class="mb-6">
          <h2 :class="['text-2xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Projects Risk Dashboard') }}</h2>
          <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Overview of ML detected risks on each project') }}</p>
        </div>

        <div v-if="allProjects && allProjects.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr :class="['border-b-2', isDarkText ? 'border-gray-300' : 'border-white/20']">
                <th :class="['text-left py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Project') }}</th>
                <th :class="['text-center py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('ML Risk Level') }}</th>
                <th :class="['text-center py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Total Risks') }}</th>
                <th :class="['text-center py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Critical/High') }}</th>
                <th :class="['text-center py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Need PO') }}</th>
                <th :class="['text-center py-4 px-4 font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Completion') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="proj in allProjects"
                :key="proj.id"
                :class="['border-b cursor-pointer transition hover:bg-white/5', isDarkText ? 'border-gray-200' : 'border-white/5']"
                @click="$inertia.visit(route('projects.show', proj.id))"
              >
                <td :class="['py-4 px-4', isDarkText ? 'text-gray-900' : 'text-white']">
                  <div class="font-semibold text-base">{{ proj.name }}</div>
                  <div :class="['text-sm mt-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ proj.code }}</div>
                </td>
                <td class="py-4 px-4 text-center">
                  <span
                    v-if="proj.risk_analysis"
                    :class="[
                      'inline-block px-4 py-1.5 rounded-full text-sm font-bold shadow-lg',
                      getRiskLevelClass(proj.risk_analysis.level)
                    ]"
                  >
                    {{ te('ml_risk_level', proj.risk_analysis.level) }}
                  </span>
                  <span v-else :class="['text-sm', isDarkText ? 'text-gray-500' : 'text-gray-500']">-</span>
                </td>
                <td :class="['py-4 px-4 text-center text-lg font-bold', isDarkText ? 'text-gray-900' : 'text-white']">
                  {{ proj.risks_count }}
                </td>
                <td class="py-4 px-4 text-center">
                  <span
                    v-if="proj.high_risks > 0"
                    class="inline-block px-4 py-1.5 rounded-full text-sm font-bold bg-red-600 text-white shadow-lg"
                  >
                    {{ proj.high_risks }}
                  </span>
                  <span v-else :class="['text-sm font-semibold', isDarkText ? 'text-green-600' : 'text-green-400']">0</span>
                </td>
                <td class="py-4 px-4 text-center">
                  <span
                    v-if="proj.need_po"
                    class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-lg"
                  >
                    {{ t('Need PO') }}
                  </span>
                  <span v-else :class="['text-sm', isDarkText ? 'text-gray-500' : 'text-gray-500']">-</span>
                </td>
                <td :class="['py-4 px-4 text-center font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">
                  <div class="flex items-center justify-center gap-3">
                    <div class="w-24 h-3 bg-gray-700 rounded-full overflow-hidden shadow-inner">
                      <div
                        class="h-full bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 transition-all"
                        :style="{ width: `${proj.completion ?? 0}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-bold min-w-[3rem]">{{ proj.completion ?? 0 }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else :class="['text-center py-8', isDarkText ? 'text-gray-600' : 'text-gray-400']">
          {{ t('No projects found') }}
        </div>
      </GlassCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import { Plus, X, Grid } from 'lucide-vue-next'

const { isDarkText } = useTheme()
const { t, te } = useTranslation()

const props = defineProps({
  risks: Object,
  projects: Array,
  allProjects: Array,
  stats: Object,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  project: props.filters?.project || '',
})

const statusOptions = computed(() => [
  { value: '', label: t('All Statuses') },
  { value: 'Identified', label: t('Identified') },
  { value: 'Assessing', label: t('Assessing') },
  { value: 'Mitigating', label: t('Mitigating') },
  { value: 'Monitoring', label: t('Monitoring') },
  { value: 'Closed', label: t('Closed') },
])

const projectOptions = computed(() => [
  { value: '', label: t('All Projects') },
  ...props.projects.map(p => ({ value: p.id, label: `${p.code} - ${p.name}` }))
])

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  router.get(route('risks.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  filters.value = { search: '', status: '', project: '' }
  applyFilters()
}

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
}

const getRiskLevelClass = (level) => {
  const classes = {
    'Critical': 'bg-red-600 text-white border-2 border-red-800',
    'High': 'bg-orange-600 text-white border-2 border-orange-800',
    'Medium': 'bg-amber-500 text-gray-900 border-2 border-amber-700',
    'Low': 'bg-green-600 text-white border-2 border-green-800',
  }
  return classes[level] || 'bg-gray-500 text-white'
}
</script>
