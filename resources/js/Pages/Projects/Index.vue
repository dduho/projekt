<template>
  <AppLayout page-title="Projects" page-description="Gestion de vos projets">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']">Projects</h1>
          <p :class="[isDarkText ? 'text-gray-700' : 'text-gray-200']">Manage all your projects and track their progress</p>
        </div>
        <div class="flex gap-3 items-center">
          <!-- View Toggle -->
          <div class="flex gap-1 bg-white/5 rounded-lg p-1">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'px-4 py-2 rounded-md transition-all text-sm font-medium',
                viewMode === 'grid' 
                  ? 'bg-indigo-500 text-white' 
                  : 'text-gray-400 hover:text-white'
              ]"
            >
              Grid
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-4 py-2 rounded-md transition-all text-sm font-medium',
                viewMode === 'list' 
                  ? 'bg-indigo-500 text-white' 
                  : 'text-gray-400 hover:text-white'
              ]"
            >
              List
            </button>
          </div>
          <GlassButton
            variant="primary"
            @click="$inertia.visit(route('projects.create'))"
            v-if="can('create projects')"
          >
            <Plus class="w-5 h-5 mr-2" />
            New Project
          </GlassButton>
        </div>
      </div>

      <!-- Filters -->
      <GlassCard>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
          <GlassInput
            v-model="filters.search"
            placeholder="Search projects..."
            @input="debouncedSearch"
          />
          <GlassSelect
            v-model="filters.rag_status"
            :options="ragStatusOptions"
            placeholder="All RAG Status"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.dev_status"
            :options="devStatusOptions"
            placeholder="All Dev Status"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.category"
            :options="categoryOptions"
            placeholder="All Categories"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.sort"
            :options="sortOptions"
            placeholder="Sort by"
            @change="applyFilters"
          />
          <GlassButton variant="secondary" @click="resetFilters">
            <X class="w-4 h-4 mr-2" />
            Reset
          </GlassButton>
        </div>
      </GlassCard>

      <!-- Projects Grid View -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="project in projects.data"
          :key="project.id"
          class="glass-card p-6 hover:scale-105 transition-transform cursor-pointer"
          @click="$inertia.visit(route('projects.show', project.id))"
        >
          <!-- Project Header -->
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1 min-w-0 pr-2">
              <Tooltip :text="project.name" position="top" class="block min-w-0">
                <h3 
                  :class="['text-lg font-bold mb-1 truncate block', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']"
                >
                  {{ project.name }}
                </h3>
              </Tooltip>
              <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ project.project_code }}</p>
            </div>
            <StatusBadge :status="project.calculated_rag_status ?? project.rag_status ?? 'gray'" />
          </div>

          <!-- Category & Priority -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div
                class="w-3 h-3 rounded-full"
                :style="{ backgroundColor: project.category?.color }"
              ></div>
              <span :class="['text-sm', isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ project.category?.name }}</span>
            </div>
            <span :class="priorityClass(project.priority)" class="text-xs px-2 py-1 rounded">
              {{ project.priority }}
            </span>
          </div>

          <!-- Status Info -->
          <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div>
              <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">FRS Status</p>
              <p :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ project.frs_status }}</p>
            </div>
            <div>
              <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Dev Status</p>
              <p :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ project.dev_status }}</p>
            </div>
          </div>

          <!-- Progress -->
          <div class="mb-4">
            <div class="flex justify-between text-sm mb-2">
              <span :class="[isDarkText ? 'text-gray-700' : 'text-gray-200']">Completion</span>
              <span :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ project.calculated_completion_percent ?? project.completion_percent ?? 0 }}%</span>
            </div>
            <ProgressBar :progress="project.calculated_completion_percent ?? project.completion_percent ?? 0" :status="project.calculated_rag_status ?? project.rag_status" />
          </div>

          <!-- Timeline -->
          <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div>
              <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Target Date</p>
              <p :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ formatDate(project.target_date) }}</p>
            </div>
            <div>
              <p :class="[isDarkText ? 'text-gray-600' : 'text-gray-400']">Owner</p>
              <p :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ project.owner?.name || '-' }}</p>
            </div>
          </div>

          <!-- Footer Stats -->
          <div class="flex gap-4 mt-4 pt-4 border-t border-white/10 text-sm">
            <div :class="['flex items-center gap-1', isDarkText ? 'text-gray-700' : 'text-gray-200']">
              <AlertTriangle class="w-4 h-4" />
              <span>{{ project.risks_count || 0 }} risks</span>
            </div>
            <div :class="['flex items-center gap-1', isDarkText ? 'text-gray-700' : 'text-gray-200']">
              <FileText class="w-4 h-4" />
              <span>{{ project.changes_count || 0 }} changes</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Projects List View -->
      <div v-if="viewMode === 'list'" class="space-y-2">
        <GlassCard
          v-for="project in projects.data"
          :key="project.id"
          class="cursor-pointer hover:scale-[1.01] transition-transform !p-0 compact-card"
          @click="$inertia.visit(route('projects.show', project.id))"
        >
          <div class="p-2">
            <!-- Header Row -->
            <div class="flex items-center justify-between mb-1">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h3 :class="['text-sm font-bold', isDarkText ? 'text-gray-900' : 'text-white']">
                    {{ project.name }}
                  </h3>
                  <StatusBadge :status="project.calculated_rag_status ?? project.rag_status ?? 'gray'" />
                </div>
                <p :class="['text-xs', isDarkText ? 'text-gray-600' : 'text-gray-400']">
                  {{ project.project_code }}
                </p>
              </div>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
              <!-- Dev Status -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Dev Status</p>
                <span :class="['text-xs px-1.5 py-0.5 rounded-full inline-block', devStatusBadgeClass(project.dev_status)]">
                  {{ project.dev_status }}
                </span>
              </div>

              <!-- Category -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Category</p>
                <div class="flex items-center gap-1">
                  <div
                    class="w-2 h-2 rounded-full"
                    :style="{ backgroundColor: project.category?.color }"
                  ></div>
                  <span :class="['text-xs', isDarkText ? 'text-gray-900' : 'text-white']">
                    {{ project.category?.name }}
                  </span>
                </div>
              </div>

              <!-- Completion -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Completion</p>
                <div class="flex items-center gap-1">
                  <div class="w-12 h-1.5 bg-gray-700 rounded-full overflow-hidden shadow-inner">
                    <div 
                      class="h-full bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 transition-all"
                      :style="{ width: `${project.calculated_completion_percent ?? project.completion_percent ?? 0}%` }"
                    ></div>
                  </div>
                  <span :class="['text-xs font-bold', isDarkText ? 'text-gray-900' : 'text-white']">
                    {{ project.calculated_completion_percent ?? project.completion_percent ?? 0 }}%
                  </span>
                </div>
              </div>

              <!-- Owner -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Owner</p>
                <p :class="['text-xs', isDarkText ? 'text-gray-900' : 'text-white']">
                  {{ project.owner?.name || '-' }}
                </p>
              </div>

              <!-- Submission Date -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Submission</p>
                <p :class="['text-xs', isDarkText ? 'text-gray-900' : 'text-white']">
                  {{ formatDate(project.submission_date) }}
                </p>
              </div>

              <!-- Target Date -->
              <div>
                <p :class="['text-xs mb-0.5', isDarkText ? 'text-gray-600' : 'text-gray-400']">Target Date</p>
                <p :class="['text-xs', isDarkText ? 'text-gray-900' : 'text-white']">
                  {{ formatDate(project.target_date) }}
                </p>
              </div>
            </div>
          </div>
        </GlassCard>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center" v-if="projects.last_page > 1">
        <div class="flex gap-2">
          <GlassButton
            variant="secondary"
            :disabled="!projects.prev_page_url"
            @click="changePage(projects.current_page - 1)"
          >
            Previous
          </GlassButton>
          <div class="flex items-center gap-2 px-4">
            <span :class="[isDarkText ? 'text-gray-900' : 'text-white']">Page {{ projects.current_page }} of {{ projects.last_page }}</span>
          </div>
          <GlassButton
            variant="secondary"
            :disabled="!projects.next_page_url"
            @click="changePage(projects.current_page + 1)"
          >
            Next
          </GlassButton>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="projects.data.length === 0" class="text-center py-12">
        <GlassCard class="max-w-md mx-auto p-8">
          <FolderOpen :class="['w-16 h-16 mx-auto mb-4', isDarkText ? 'text-gray-600' : 'text-gray-400']" />
          <h3 :class="['text-xl font-semibold mb-2', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']">No projects found</h3>
          <p :class="['mb-6', isDarkText ? 'text-gray-700' : 'text-gray-200']">
            {{ filters.search || filters.rag_status || filters.category ? 'Try adjusting your filters' : 'Get started by creating your first project' }}
          </p>
          <GlassButton
            variant="primary"
            @click="$inertia.visit(route('projects.create'))"
            v-if="can('create projects')"
          >
            <Plus class="w-5 h-5 mr-2" />
            Create Project
          </GlassButton>
        </GlassCard>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme';
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import Tooltip from '@/Components/Glass/Tooltip.vue'
import { Plus, Search, X, AlertTriangle, FileText, FolderOpen } from 'lucide-vue-next'

const { isDarkText } = useTheme();

const props = defineProps({
  projects: Object,
  categories: Array,
  filters: Object,
})

const viewMode = ref('grid')

const filters = ref({
  search: props.filters?.search || '',
  rag_status: props.filters?.rag_status || '',
  dev_status: props.filters?.dev_status || '',
  category: props.filters?.category || '',
  sort: props.filters?.sort || '',
})

const ragStatusOptions = [
  { value: '', label: 'All RAG Status' },
  { value: 'Green', label: 'Green - On Track' },
  { value: 'Amber', label: 'Amber - At Risk' },
  { value: 'Red', label: 'Red - Critical' },
]

const devStatusOptions = [
  { value: '', label: 'All Dev Status' },
  { value: 'Not Started', label: 'Not Started' },
  { value: 'In Development', label: 'In Development' },
  { value: 'Testing', label: 'Testing' },
  { value: 'UAT', label: 'UAT' },
  { value: 'Deployed', label: 'Deployed' },
]

const sortOptions = [
  { value: '', label: 'Default Sort' },
  { value: 'name_asc', label: 'Name (A-Z)' },
  { value: 'name_desc', label: 'Name (Z-A)' },
  { value: 'completion_desc', label: 'Completion (High-Low)' },
  { value: 'completion_asc', label: 'Completion (Low-High)' },
  { value: 'target_date_asc', label: 'Target Date (Near-Far)' },
  { value: 'target_date_desc', label: 'Target Date (Far-Near)' },
  { value: 'created_desc', label: 'Recently Created' },
  { value: 'created_asc', label: 'Oldest First' },
]

const categoryOptions = computed(() => [
  { value: '', label: 'All Categories' },
  ...(props.categories?.map(cat => ({ value: cat.id, label: cat.name })) || [])
])

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  router.get(route('projects.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  filters.value = { search: '', rag_status: '', dev_status: '', category: '', sort: '' }
  applyFilters()
}

const changePage = (page) => {
  router.get(route('projects.index'), { ...filters.value, page }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const can = (permission) => {
  const user = usePage().props?.auth?.user
  return user?.permissions?.includes(permission) || user?.roles?.includes('admin')
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const priorityClass = (priority) => {
  const classes = {
    'High': 'bg-red-500/20 text-red-400',
    'Medium': 'bg-amber-500/20 text-amber-400',
    'Low': 'bg-green-500/20 text-green-400',
  }
  return classes[priority] || 'bg-slate-500/20 text-slate-400'
}

const devStatusBadgeClass = (status) => {
  const classes = {
    'Not Started': 'bg-gray-600 text-white font-medium',
    'In Development': 'bg-blue-600 text-white font-medium',
    'Testing': 'bg-purple-600 text-white font-medium',
    'UAT': 'bg-amber-600 text-white font-medium',
    'Deployed': 'bg-green-600 text-white font-medium',
  }
  return classes[status] || 'bg-gray-600 text-white font-medium'
}
</script>
<style scoped>
.compact-card :deep(> div) {
  padding-top: 0 !important;
}
</style>