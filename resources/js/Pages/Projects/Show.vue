<template>
  <AppLayout page-title="Détails Projet" page-description="Informations du projet">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <GlassButton
            variant="ghost"
            @click="$inertia.visit(route('projects.index'))"
          >
            <ArrowLeft class="w-5 h-5" />
          </GlassButton>
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 :class="['text-3xl font-bold', textPrimary]">{{ project.name }}</h1>
              <StatusBadge :status="project.calculated_rag_status ?? project.rag_status ?? 'gray'" />
            </div>
            <p :class="textSecondary">{{ project.project_code }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <GlassButton
            variant="secondary"
            @click="$inertia.visit(route('projects.edit', project.id))"
            v-if="can('edit projects')"
          >
            <Edit class="w-4 h-4 mr-2" />
            Edit
          </GlassButton>
          <GlassButton
            variant="danger"
            @click="confirmDelete"
            v-if="can('delete projects')"
          >
            <Trash2 class="w-4 h-4" />
          </GlassButton>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">Completion</p>
              <p :class="['text-2xl font-bold', textPrimary]">{{ project.calculated_completion_percent ?? project.completion_percent ?? 0 }}%</p>
            </div>
            <TrendingUp class="w-8 h-8 text-prism-400" />
          </div>
          <ProgressBar :progress="project.calculated_completion_percent ?? project.completion_percent ?? 0" :status="project.calculated_rag_status ?? project.rag_status" class="mt-3" />
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">Priority</p>
              <p class="text-2xl font-bold" :class="priorityTextClass(project.priority)">{{ project.priority || '-' }}</p>
            </div>
            <Flag class="w-8 h-8" :class="priorityTextClass(project.priority)" />
          </div>
          <p :class="['text-sm mt-2', textSecondary]">FRS: {{ project.frs_status || '-' }}</p>
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">Risks</p>
              <p :class="['text-2xl font-bold', textPrimary]">{{ project.risks_count || 0 }}</p>
            </div>
            <AlertTriangle class="w-8 h-8 text-yellow-400" />
          </div>
          <GlassButton
            variant="ghost"
            size="sm"
            class="mt-2 w-full"
            @click="activeTab = 'risks'"
          >
            View Risks
          </GlassButton>
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">Changes</p>
              <p :class="['text-2xl font-bold', textPrimary]">{{ project.changes_count || 0 }}</p>
            </div>
            <FileText class="w-8 h-8 text-blue-400" />
          </div>
          <GlassButton
            variant="ghost"
            size="sm"
            class="mt-2 w-full"
            @click="activeTab = 'changes'"
          >
            View Changes
          </GlassButton>
        </GlassCard>
      </div>

      <!-- Details & Tabs -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="space-y-4">
          <GlassCard>
            <h3 :class="['text-lg font-semibold mb-4', textPrimary]">Project Details</h3>
            <div class="space-y-3">
              <div>
                <p :class="[textMuted, 'text-sm']">Category</p>
                <div class="flex items-center gap-2 mt-1">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: project.category?.color || '#6366f1' }"
                  ></div>
                  <p :class="textPrimary">{{ project.category?.name || '-' }}</p>
                </div>
              </div>
              <div v-if="project.business_area">
                <p :class="[textMuted, 'text-sm']">Business Area</p>
                <p :class="textPrimary">{{ project.business_area }}</p>
              </div>
              <div>
                <p :class="[textMuted, 'text-sm']">Dev Status</p>
                <p :class="textPrimary">{{ project.dev_status || '-' }}</p>
              </div>
              <div>
                <p :class="[textMuted, 'text-sm']">Target Date</p>
                <p :class="textPrimary">{{ formatDate(project.target_date) }}</p>
              </div>
              <div v-if="project.submission_date">
                <p :class="[textMuted, 'text-sm']">Submission Date</p>
                <p :class="textPrimary">{{ formatDate(project.submission_date) }}</p>
              </div>
              <div v-if="project.planned_release">
                <p :class="[textMuted, 'text-sm']">Planned Release</p>
                <p :class="textPrimary">{{ project.planned_release }}</p>
              </div>
              <div v-if="project.owner">
                <p :class="[textMuted, 'text-sm']">Owner</p>
                <p :class="textPrimary">{{ project.owner.name }}</p>
              </div>
              <div v-if="project.current_progress">
                <p :class="[textMuted, 'text-sm']">Current Progress</p>
                <p :class="textPrimary">{{ project.current_progress }}</p>
              </div>
            </div>
          </GlassCard>

          <GlassCard v-if="project.blockers">
            <h3 :class="['text-lg font-semibold mb-3 flex items-center gap-2', textPrimary]">
              <AlertCircle class="w-5 h-5 text-red-400" />
              Blockers
            </h3>
            <p :class="[textSecondary, 'text-sm']">{{ project.blockers }}</p>
          </GlassCard>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Tabs -->
          <GlassCard>
            <div :class="['flex border-b', borderColor]">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'px-6 py-3 font-medium transition-colors',
                  activeTab === tab.id
                    ? (isDarkText ? 'text-prism-600 border-b-2 border-prism-500' : 'text-white border-b-2 border-prism-500')
                    : (isDarkText ? 'text-gray-500 hover:text-gray-900' : 'text-slate-400 hover:text-white')
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <div class="mt-6">
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'">
                <h3 :class="['text-lg font-semibold mb-3', textPrimary]">Description</h3>
                <p :class="[textSecondary, 'mb-6']">{{ project.description || 'No description provided' }}</p>

                <div class="flex justify-between items-center mb-3">
                  <h3 :class="['text-lg font-semibold', textPrimary]">Phases</h3>
                  <div v-if="can('edit projects')" class="flex items-center gap-2">
                    <span :class="['text-sm', textMuted]">{{ completedPhasesCount }}/{{ project.phases?.length || 0 }} completed</span>
                  </div>
                </div>
                
                <!-- Phase Timeline -->
                <div v-if="project.phases?.length" class="space-y-2">
                  <div
                    v-for="(phase, index) in project.phases"
                    :key="phase.id"
                    :class="[
                      'relative p-4 rounded-lg border-2 transition-all duration-200',
                      getPhaseCardClass(phase.status)
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <!-- Phase Number -->
                        <div :class="[
                          'w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm',
                          getPhaseNumberClass(phase.status)
                        ]">
                          <Check v-if="phase.status === 'Completed'" class="w-5 h-5" />
                          <span v-else>{{ index + 1 }}</span>
                        </div>
                        
                        <div>
                          <h4 :class="['font-semibold', textPrimary]">{{ phase.phase }}</h4>
                          <div :class="['text-xs', textMuted]" v-if="phase.started_at || phase.completed_at">
                            <span v-if="phase.started_at">Started: {{ formatDate(phase.started_at) }}</span>
                            <span v-if="phase.completed_at"> • Completed: {{ formatDate(phase.completed_at) }}</span>
                          </div>
                          <p :class="['text-sm mt-1', textMuted]" v-if="phase.remarks">{{ phase.remarks }}</p>
                        </div>
                      </div>
                      
                      <!-- Status Selector -->
                      <div v-if="can('edit projects')" class="flex items-center gap-2">
                        <select
                          :value="phase.status"
                          @change="updatePhaseStatus(phase, $event.target.value)"
                          :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium border cursor-pointer',
                            getPhaseSelectClass(phase.status)
                          ]"
                        >
                          <option value="Pending">⏳ Pending</option>
                          <option value="In Progress">🔄 In Progress</option>
                          <option value="Completed">✅ Completed</option>
                          <option value="Blocked">🚫 Blocked</option>
                        </select>
                      </div>
                      <StatusBadge v-else :status="phaseStatusToRag(phase.status)" size="sm" />
                    </div>
                    
                    <!-- Progress Line -->
                    <div v-if="index < project.phases.length - 1" 
                      :class="[
                        'absolute left-7 top-14 w-0.5 h-4',
                        phase.status === 'Completed' ? 'bg-green-500' : (isDarkText ? 'bg-gray-300' : 'bg-white/20')
                      ]"
                    ></div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  No phases defined
                </div>

                <!-- Priority Section -->
                <div class="mt-6 pt-6" :class="[borderColor, 'border-t']">
                  <div class="flex justify-between items-center">
                    <h3 :class="['text-lg font-semibold', textPrimary]">Priority</h3>
                    <div v-if="can('edit projects')" class="flex gap-2">
                      <button
                        v-for="priority in ['Low', 'Medium', 'High']"
                        :key="priority"
                        @click="updatePriority(priority)"
                        :class="[
                          'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                          project.priority === priority 
                            ? getPriorityActiveClass(priority)
                            : (isDarkText ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-white/10 text-gray-300 hover:bg-white/20')
                        ]"
                      >
                        {{ priority }}
                      </button>
                    </div>
                    <span v-else :class="priorityTextClass(project.priority)">{{ project.priority }}</span>
                  </div>
                </div>
              </div>

              <!-- Risks Tab -->
              <div v-if="activeTab === 'risks'">
                <div class="flex justify-between items-center mb-4">
                  <h3 :class="['text-lg font-semibold', textPrimary]">Risks</h3>
                  <GlassButton
                    variant="primary"
                    size="sm"
                    @click="createRisk"
                    v-if="can('create risks')"
                  >
                    <Plus class="w-4 h-4 mr-2" />
                    Add Risk
                  </GlassButton>
                </div>
                <div v-if="project.risks?.length" class="space-y-3">
                  <div
                    v-for="risk in project.risks"
                    :key="risk.id"
                    :class="['p-4 rounded-lg cursor-pointer transition', isDarkText ? 'bg-gray-100 hover:bg-gray-200' : 'glass hover:bg-white/10']"
                    @click="viewRisk(risk.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <p :class="['text-sm mb-2', textSecondary]">{{ risk.description }}</p>
                        <div class="flex gap-3 text-sm">
                          <span :class="textMuted">Impact: {{ risk.impact }}</span>
                          <span :class="textMuted">Probability: {{ risk.probability }}</span>
                          <span :class="riskScoreClass(risk.risk_score)">Score: {{ risk.risk_score }}</span>
                        </div>
                      </div>
                      <StatusBadge :status="riskStatusToRag(risk.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  No risks recorded
                </div>
              </div>

              <!-- Changes Tab -->
              <div v-if="activeTab === 'changes'">
                <div class="flex justify-between items-center mb-4">
                  <h3 :class="['text-lg font-semibold', textPrimary]">Change Requests</h3>
                  <GlassButton
                    variant="primary"
                    size="sm"
                    @click="createChange"
                    v-if="can('create change-requests')"
                  >
                    <Plus class="w-4 h-4 mr-2" />
                    Add Change
                  </GlassButton>
                </div>
                <div v-if="project.changes?.length" class="space-y-3">
                  <div
                    v-for="change in project.changes"
                    :key="change.id"
                    :class="['p-4 rounded-lg cursor-pointer transition', isDarkText ? 'bg-gray-100 hover:bg-gray-200' : 'glass hover:bg-white/10']"
                    @click="viewChange(change.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span class="text-xs px-2 py-1 rounded bg-prism-500/20 text-prism-600">
                            {{ change.change_type }}
                          </span>
                          <span :class="['text-xs', textMuted]">{{ change.change_code }}</span>
                        </div>
                        <p :class="['text-sm mb-2', textSecondary]">{{ change.description }}</p>
                        <p :class="['text-xs', textMuted]">
                          Requested by {{ change.requested_by?.name }} on {{ formatDate(change.requested_at) }}
                        </p>
                      </div>
                      <StatusBadge :status="changeStatusToRag(change.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  No change requests
                </div>
              </div>

              <!-- Activities Tab -->
              <div v-if="activeTab === 'activities'">
                <h3 :class="['text-lg font-semibold mb-4', textPrimary]">Recent Activities</h3>
                <div v-if="project.activities?.length" class="space-y-3">
                  <div
                    v-for="activity in project.activities"
                    :key="activity.id"
                    :class="['flex gap-3 pb-3 border-b last:border-0', borderColor]"
                  >
                    <div class="w-2 h-2 rounded-full bg-prism-400 mt-2"></div>
                    <div class="flex-1">
                      <p :class="[textPrimary, 'text-sm']">{{ activity.description }}</p>
                      <p :class="['text-xs mt-1', textMuted]">{{ formatDateTime(activity.created_at) }} by {{ activity.user?.name }}</p>
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  No activities yet
                </div>
              </div>
            </div>
          </GlassCard>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <GlassModal v-model="showDeleteModal" title="Delete Project">
      <p :class="[textSecondary, 'mb-6']">
        Are you sure you want to delete this project? This action cannot be undone.
      </p>
      <div class="flex justify-end gap-3">
        <GlassButton variant="secondary" @click="showDeleteModal = false">
          Cancel
        </GlassButton>
        <GlassButton variant="danger" @click="deleteProject">
          Delete Project
        </GlassButton>
      </div>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import {
  ArrowLeft, Edit, Trash2, TrendingUp, Flag,
  AlertTriangle, FileText, AlertCircle, Plus, Check
} from 'lucide-vue-next'

const props = defineProps({
  project: Object,
})

const { isDarkText } = useTheme()

// Classes de texte dynamiques selon le thème
const textPrimary = computed(() => isDarkText.value ? 'text-gray-900' : 'text-white')
const textSecondary = computed(() => isDarkText.value ? 'text-gray-700' : 'text-slate-300')
const textMuted = computed(() => isDarkText.value ? 'text-gray-500' : 'text-slate-400')
const borderColor = computed(() => isDarkText.value ? 'border-gray-200' : 'border-white/10')

const activeTab = ref('overview')
const showDeleteModal = ref(false)

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'risks', label: 'Risks' },
  { id: 'changes', label: 'Changes' },
  { id: 'activities', label: 'Activities' },
]

const can = (permission) => {
  const user = window.$page?.props?.auth?.user;
  // Admin a toutes les permissions
  if (user?.roles?.includes('admin') || user?.role === 'admin') {
    return true;
  }
  return user?.permissions?.includes(permission);
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const formatDateTime = (date) => {
  return date ? new Date(date).toLocaleString('fr-FR') : '-'
}

const priorityTextClass = (priority) => {
  const classes = {
    'High': 'text-red-400',
    'Medium': 'text-amber-400',
    'Low': 'text-green-400',
  }
  return classes[priority] || 'text-slate-400'
}

const riskScoreClass = (score) => {
  const classes = {
    'Critical': 'text-red-400 font-semibold',
    'High': 'text-orange-400',
    'Medium': 'text-amber-400',
    'Low': 'text-green-400',
  }
  return classes[score] || 'text-slate-400'
}

const phaseStatusToRag = (status) => {
  const map = {
    'Completed': 'Green',
    'In Progress': 'Amber',
    'Pending': 'Amber',
    'Blocked': 'Red',
  }
  return map[status] || 'Amber'
}

const riskStatusToRag = (status) => {
  const map = {
    'Closed': 'Green',
    'Mitigated': 'Green',
    'In Progress': 'Amber',
    'Open': 'Red',
  }
  return map[status] || 'Amber'
}

const changeStatusToRag = (status) => {
  const map = {
    'Approved': 'Green',
    'Under Review': 'Amber',
    'Pending': 'Amber',
    'Rejected': 'Red',
  }
  return map[status] || 'Amber'
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteProject = () => {
  router.delete(route('projects.destroy', props.project.id))
}

const createRisk = () => {
  router.visit(route('risks.create', { project_id: props.project.id }))
}

const viewRisk = (id) => {
  router.visit(route('risks.show', id))
}

const createChange = () => {
  router.visit(route('change-requests.create', { project_id: props.project.id }))
}

const viewChange = (id) => {
  router.visit(route('change-requests.show', id))
}

// Phase management
const completedPhasesCount = computed(() => {
  return props.project.phases?.filter(p => p.status === 'Completed').length || 0
})

const updatePhaseStatus = (phase, newStatus) => {
  router.put(route('phases.update-status', phase.id), {
    status: newStatus,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

const getPhaseCardClass = (status) => {
  if (isDarkText.value) {
    const classes = {
      'Completed': 'bg-green-50 border-green-300',
      'In Progress': 'bg-blue-50 border-blue-300',
      'Blocked': 'bg-red-50 border-red-300',
      'Pending': 'bg-gray-50 border-gray-200',
    }
    return classes[status] || 'bg-gray-50 border-gray-200'
  } else {
    const classes = {
      'Completed': 'bg-green-500/10 border-green-500/50',
      'In Progress': 'bg-blue-500/10 border-blue-500/50',
      'Blocked': 'bg-red-500/10 border-red-500/50',
      'Pending': 'bg-white/5 border-white/10',
    }
    return classes[status] || 'bg-white/5 border-white/10'
  }
}

const getPhaseNumberClass = (status) => {
  if (isDarkText.value) {
    const classes = {
      'Completed': 'bg-green-500 text-white',
      'In Progress': 'bg-blue-500 text-white',
      'Blocked': 'bg-red-500 text-white',
      'Pending': 'bg-gray-300 text-gray-600',
    }
    return classes[status] || 'bg-gray-300 text-gray-600'
  } else {
    const classes = {
      'Completed': 'bg-green-500 text-white',
      'In Progress': 'bg-blue-500 text-white',
      'Blocked': 'bg-red-500 text-white',
      'Pending': 'bg-white/20 text-white',
    }
    return classes[status] || 'bg-white/20 text-white'
  }
}

const getPhaseSelectClass = (status) => {
  const classes = {
    'Completed': 'bg-green-100 text-green-800 border-green-300',
    'In Progress': 'bg-blue-100 text-blue-800 border-blue-300',
    'Blocked': 'bg-red-100 text-red-800 border-red-300',
    'Pending': 'bg-gray-100 text-gray-600 border-gray-300',
  }
  return classes[status] || 'bg-gray-100 text-gray-600 border-gray-300'
}

const getPriorityActiveClass = (priority) => {
  const classes = {
    'High': 'bg-red-500 text-white',
    'Medium': 'bg-amber-500 text-white',
    'Low': 'bg-green-500 text-white',
  }
  return classes[priority] || 'bg-gray-500 text-white'
}

const updatePriority = (priority) => {
  router.put(route('projects.update', props.project.id), {
    priority: priority,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>
