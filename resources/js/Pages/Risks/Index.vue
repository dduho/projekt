<template>
  <AppLayout page-title="Risques" page-description="Gestion des risques">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']">Risks Management</h1>
          <p :class="[isDarkText ? 'text-gray-700' : 'text-gray-200']">Identify, assess and mitigate project risks</p>
        </div>
        <div class="flex gap-2">
          <GlassButton 
            variant="secondary"
            @click="$inertia.visit(route('risks.matrix'))"
          >
            <Grid class="w-5 h-5 mr-2" />
            Risk Matrix
          </GlassButton>
          <GlassButton 
            variant="primary" 
            @click="$inertia.visit(route('risks.create'))"
            v-if="can('create risks')"
          >
            <Plus class="w-5 h-5 mr-2" />
            New Risk
          </GlassButton>
        </div>
      </div>

      <!-- Filters -->
      <GlassCard>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <GlassInput
            v-model="filters.search"
            placeholder="Search risks..."
            :icon="Search"
            @input="debouncedSearch"
          />
          <GlassSelect
            v-model="filters.status"
            :options="statusOptions"
            placeholder="All Statuses"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.project"
            :options="projectOptions"
            placeholder="All Projects"
            @change="applyFilters"
          />
          <GlassButton variant="secondary" @click="resetFilters">
            <X class="w-4 h-4 mr-2" />
            Reset
          </GlassButton>
        </div>
      </GlassCard>

      <!-- Risk Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <GlassCard>
          <div class="text-center">
            <p :class="['text-4xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ stats.total }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Total Risks</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-red-400 mb-2">{{ stats.critical }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Critical (Score > 15)</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-yellow-400 mb-2">{{ stats.high }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">High (Score 10-15)</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-4xl font-bold text-green-400 mb-2">{{ stats.low }}</p>
            <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Low (Score &lt; 10)</p>
          </div>
        </GlassCard>
      </div>

      <!-- Risks List -->
      <GlassCard>
        <div class="space-y-3">
          <div 
            v-for="risk in risks.data" 
            :key="risk.id"
            class="glass p-4 rounded-lg hover:bg-white/10 cursor-pointer transition"
            @click="$inertia.visit(route('risks.show', risk.id))"
          >
            <div class="flex justify-between items-start mb-3">
              <div class="flex-1">
                <h3 :class="['text-lg font-semibold mb-1', isDarkText ? 'text-gray-900' : 'text-white']">{{ risk.title }}</h3>
                <p :class="['text-sm mb-2', isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ risk.description }}</p>
                <div :class="['flex items-center gap-2 text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">
                  <FolderOpen class="w-4 h-4" />
                  <span>{{ risk.project?.name }}</span>
                  <span class="text-slate-600">•</span>
                  <span>{{ risk.project?.code }}</span>
                </div>
              </div>
              <StatusBadge :status="risk.status" />
            </div>

            <div class="grid grid-cols-4 gap-4">
              <div>
                <p :class="['text-xs mb-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">Impact</p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-slate-700 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-green-500 to-red-500"
                      :style="{ width: `${(risk.impact / 5) * 100}%` }"
                    ></div>
                  </div>
                  <span :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ risk.impact }}/5</span>
                </div>
              </div>
              <div>
                <p :class="['text-xs mb-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">Likelihood</p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-slate-700 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-green-500 to-red-500"
                      :style="{ width: `${(risk.likelihood / 5) * 100}%` }"
                    ></div>
                  </div>
                  <span :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ risk.likelihood }}/5</span>
                </div>
              </div>
              <div>
                <p :class="['text-xs mb-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">Risk Score</p>
                <div 
                  :class="[
                    'inline-block px-3 py-1 rounded-full text-sm font-semibold',
                    getRiskScoreClass(risk.impact * risk.likelihood)
                  ]"
                >
                  {{ risk.impact * risk.likelihood }}
                </div>
              </div>
              <div>
                <p :class="['text-xs mb-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">Identified</p>
                <p :class="['text-sm', isDarkText ? 'text-gray-900' : 'text-white']">{{ formatDate(risk.identified_date) }}</p>
              </div>
            </div>

            <div v-if="risk.mitigation" class="mt-3 pt-3 border-t border-white/10">
              <p :class="['text-xs mb-1', isDarkText ? 'text-gray-600' : 'text-gray-400']">Mitigation Strategy</p>
              <p :class="['text-sm', isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ risk.mitigation }}</p>
            </div>
          </div>
        </div>
      </GlassCard>

      <!-- Pagination -->
      <div class="flex justify-center" v-if="risks.last_page > 1">
        <div class="flex gap-2">
          <GlassButton 
            variant="secondary" 
            :disabled="!risks.prev_page_url"
            @click="changePage(risks.current_page - 1)"
          >
            Previous
          </GlassButton>
          <div class="flex items-center gap-2 px-4">
            <span :class="[isDarkText ? 'text-gray-900' : 'text-white']">Page {{ risks.current_page }} of {{ risks.last_page }}</span>
          </div>
          <GlassButton 
            variant="secondary" 
            :disabled="!risks.next_page_url"
            @click="changePage(risks.current_page + 1)"
          >
            Next
          </GlassButton>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="risks.data.length === 0" class="text-center py-12">
        <GlassCard class="max-w-md mx-auto p-8">
          <AlertTriangle :class="['w-16 h-16 mx-auto mb-4', isDarkText ? 'text-gray-600' : 'text-gray-400']" />
          <h3 :class="['text-xl font-semibold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">No risks found</h3>
          <p :class="['mb-6', isDarkText ? 'text-gray-700' : 'text-gray-200']">
            {{ filters.search || filters.status || filters.project ? 'Try adjusting your filters' : 'Start by identifying potential risks' }}
          </p>
          <GlassButton 
            variant="primary" 
            @click="$inertia.visit(route('risks.create'))"
            v-if="can('create risks')"
          >
            <Plus class="w-5 h-5 mr-2" />
            Create Risk
          </GlassButton>
        </GlassCard>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme';

const { isDarkText } = useTheme();
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import { Plus, Search, X, AlertTriangle, FolderOpen, Grid } from 'lucide-vue-next'

const props = defineProps({
  risks: Object,
  projects: Array,
  stats: Object,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  project: props.filters?.project || '',
})

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'Identified', label: 'Identified' },
  { value: 'Assessing', label: 'Assessing' },
  { value: 'Mitigating', label: 'Mitigating' },
  { value: 'Monitoring', label: 'Monitoring' },
  { value: 'Closed', label: 'Closed' },
]

const projectOptions = computed(() => [
  { value: '', label: 'All Projects' },
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

const changePage = (page) => {
  router.get(route('risks.index', { ...filters.value, page }), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const getRiskScoreClass = (score) => {
  if (score >= 16) return 'bg-red-500/20 text-red-400'
  if (score >= 10) return 'bg-yellow-500/20 text-yellow-400'
  return 'bg-green-500/20 text-green-400'
}
</script>
