<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Projects</h1>
          <p class="text-slate-300">Manage all your projects and track their progress</p>
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

      <!-- Filters -->
      <GlassCard>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <GlassInput
            v-model="filters.search"
            placeholder="Search projects..."
            icon="Search"
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
          <GlassButton variant="secondary" @click="resetFilters">
            <X class="w-4 h-4 mr-2" />
            Reset
          </GlassButton>
        </div>
      </GlassCard>

      <!-- Projects Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="project in projects.data"
          :key="project.id"
          class="glass-card p-6 hover:scale-105 transition-transform cursor-pointer"
          @click="$inertia.visit(route('projects.show', project.id))"
        >
          <!-- Project Header -->
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-xl font-bold text-white mb-1">{{ project.name }}</h3>
              <p class="text-sm text-slate-400">{{ project.project_code }}</p>
            </div>
            <StatusBadge :status="project.rag_status" />
          </div>

          <!-- Category & Priority -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div
                class="w-3 h-3 rounded-full"
                :style="{ backgroundColor: project.category?.color }"
              ></div>
              <span class="text-sm text-slate-300">{{ project.category?.name }}</span>
            </div>
            <span :class="priorityClass(project.priority)" class="text-xs px-2 py-1 rounded">
              {{ project.priority }}
            </span>
          </div>

          <!-- Status Info -->
          <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div>
              <p class="text-slate-400">FRS Status</p>
              <p class="text-white">{{ project.frs_status }}</p>
            </div>
            <div>
              <p class="text-slate-400">Dev Status</p>
              <p class="text-white">{{ project.dev_status }}</p>
            </div>
          </div>

          <!-- Progress -->
          <div class="mb-4">
            <div class="flex justify-between text-sm mb-2">
              <span class="text-slate-300">Completion</span>
              <span class="text-white font-semibold">{{ project.completion_percent }}%</span>
            </div>
            <ProgressBar :progress="project.completion_percent" :status="project.rag_status" />
          </div>

          <!-- Timeline -->
          <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div>
              <p class="text-slate-400">Target Date</p>
              <p class="text-white">{{ formatDate(project.target_date) }}</p>
            </div>
            <div>
              <p class="text-slate-400">Owner</p>
              <p class="text-white">{{ project.owner?.name || '-' }}</p>
            </div>
          </div>

          <!-- Footer Stats -->
          <div class="flex gap-4 mt-4 pt-4 border-t border-white/10 text-sm">
            <div class="flex items-center gap-1 text-slate-300">
              <AlertTriangle class="w-4 h-4" />
              <span>{{ project.risks_count || 0 }} risks</span>
            </div>
            <div class="flex items-center gap-1 text-slate-300">
              <FileText class="w-4 h-4" />
              <span>{{ project.changes_count || 0 }} changes</span>
            </div>
          </div>
        </div>
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
            <span class="text-white">Page {{ projects.current_page }} of {{ projects.last_page }}</span>
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
          <FolderOpen class="w-16 h-16 mx-auto text-slate-400 mb-4" />
          <h3 class="text-xl font-semibold text-white mb-2">No projects found</h3>
          <p class="text-slate-300 mb-6">
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
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import { Plus, Search, X, AlertTriangle, FileText, FolderOpen } from 'lucide-vue-next'

const props = defineProps({
  projects: Object,
  categories: Array,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  rag_status: props.filters?.rag_status || '',
  dev_status: props.filters?.dev_status || '',
  category: props.filters?.category || '',
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
  filters.value = { search: '', rag_status: '', dev_status: '', category: '' }
  applyFilters()
}

const changePage = (page) => {
  router.get(route('projects.index', { ...filters.value, page }), {}, {
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

const priorityClass = (priority) => {
  const classes = {
    'High': 'bg-red-500/20 text-red-400',
    'Medium': 'bg-amber-500/20 text-amber-400',
    'Low': 'bg-green-500/20 text-green-400',
  }
  return classes[priority] || 'bg-slate-500/20 text-slate-400'
}
</script>
