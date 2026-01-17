<template>
  <AppLayout>
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
              <h1 class="text-3xl font-bold text-white">{{ project.name }}</h1>
              <StatusBadge :status="project.rag_status" />
            </div>
            <p class="text-slate-300">{{ project.project_code }}</p>
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
              <p class="text-slate-400 text-sm mb-1">Completion</p>
              <p class="text-2xl font-bold text-white">{{ project.completion_percent }}%</p>
            </div>
            <TrendingUp class="w-8 h-8 text-prism-400" />
          </div>
          <ProgressBar :progress="project.completion_percent" :status="project.rag_status" class="mt-3" />
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm mb-1">Priority</p>
              <p class="text-2xl font-bold" :class="priorityTextClass(project.priority)">{{ project.priority }}</p>
            </div>
            <Flag class="w-8 h-8" :class="priorityTextClass(project.priority)" />
          </div>
          <p class="text-sm text-slate-300 mt-2">FRS: {{ project.frs_status }}</p>
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-400 text-sm mb-1">Risks</p>
              <p class="text-2xl font-bold text-white">{{ project.risks_count || 0 }}</p>
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
              <p class="text-slate-400 text-sm mb-1">Changes</p>
              <p class="text-2xl font-bold text-white">{{ project.changes_count || 0 }}</p>
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
            <h3 class="text-lg font-semibold text-white mb-4">Project Details</h3>
            <div class="space-y-3">
              <div>
                <p class="text-slate-400 text-sm">Category</p>
                <div class="flex items-center gap-2 mt-1">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: project.category?.color }"
                  ></div>
                  <p class="text-white">{{ project.category?.name }}</p>
                </div>
              </div>
              <div v-if="project.business_area">
                <p class="text-slate-400 text-sm">Business Area</p>
                <p class="text-white">{{ project.business_area }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Dev Status</p>
                <p class="text-white">{{ project.dev_status }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Target Date</p>
                <p class="text-white">{{ formatDate(project.target_date) }}</p>
              </div>
              <div v-if="project.submission_date">
                <p class="text-slate-400 text-sm">Submission Date</p>
                <p class="text-white">{{ formatDate(project.submission_date) }}</p>
              </div>
              <div v-if="project.planned_release">
                <p class="text-slate-400 text-sm">Planned Release</p>
                <p class="text-white">{{ project.planned_release }}</p>
              </div>
              <div v-if="project.owner">
                <p class="text-slate-400 text-sm">Owner</p>
                <p class="text-white">{{ project.owner.name }}</p>
              </div>
              <div v-if="project.current_progress">
                <p class="text-slate-400 text-sm">Current Progress</p>
                <p class="text-white">{{ project.current_progress }}</p>
              </div>
            </div>
          </GlassCard>

          <GlassCard v-if="project.blockers">
            <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
              <AlertCircle class="w-5 h-5 text-red-400" />
              Blockers
            </h3>
            <p class="text-slate-300 text-sm">{{ project.blockers }}</p>
          </GlassCard>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Tabs -->
          <GlassCard>
            <div class="flex border-b border-white/10">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'px-6 py-3 font-medium transition-colors',
                  activeTab === tab.id
                    ? 'text-white border-b-2 border-prism-500'
                    : 'text-slate-400 hover:text-white'
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <div class="mt-6">
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'">
                <h3 class="text-lg font-semibold text-white mb-3">Description</h3>
                <p class="text-slate-300 mb-6">{{ project.description || 'No description provided' }}</p>

                <h3 class="text-lg font-semibold text-white mb-3">Phases</h3>
                <div class="space-y-3">
                  <div
                    v-for="phase in project.phases"
                    :key="phase.id"
                    class="glass p-4 rounded-lg"
                  >
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <h4 class="text-white font-semibold">{{ phase.phase }}</h4>
                        <p class="text-sm text-slate-400" v-if="phase.remarks">{{ phase.remarks }}</p>
                      </div>
                      <StatusBadge :status="phaseStatusToRag(phase.status)" size="sm" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Risks Tab -->
              <div v-if="activeTab === 'risks'">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-white">Risks</h3>
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
                    class="glass p-4 rounded-lg hover:bg-white/10 cursor-pointer transition"
                    @click="viewRisk(risk.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <p class="text-sm text-slate-300 mb-2">{{ risk.description }}</p>
                        <div class="flex gap-3 text-sm">
                          <span class="text-slate-400">Impact: {{ risk.impact }}</span>
                          <span class="text-slate-400">Probability: {{ risk.probability }}</span>
                          <span :class="riskScoreClass(risk.risk_score)">Score: {{ risk.risk_score }}</span>
                        </div>
                      </div>
                      <StatusBadge :status="riskStatusToRag(risk.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400">
                  No risks recorded
                </div>
              </div>

              <!-- Changes Tab -->
              <div v-if="activeTab === 'changes'">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-white">Change Requests</h3>
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
                    class="glass p-4 rounded-lg hover:bg-white/10 cursor-pointer transition"
                    @click="viewChange(change.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span class="text-xs px-2 py-1 rounded bg-prism-500/20 text-prism-400">
                            {{ change.change_type }}
                          </span>
                          <span class="text-xs text-slate-400">{{ change.change_code }}</span>
                        </div>
                        <p class="text-sm text-slate-300 mb-2">{{ change.description }}</p>
                        <p class="text-xs text-slate-400">
                          Requested by {{ change.requested_by?.name }} on {{ formatDate(change.requested_at) }}
                        </p>
                      </div>
                      <StatusBadge :status="changeStatusToRag(change.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400">
                  No change requests
                </div>
              </div>

              <!-- Activities Tab -->
              <div v-if="activeTab === 'activities'">
                <h3 class="text-lg font-semibold text-white mb-4">Recent Activities</h3>
                <div v-if="project.activities?.length" class="space-y-3">
                  <div
                    v-for="activity in project.activities"
                    :key="activity.id"
                    class="flex gap-3 pb-3 border-b border-white/10 last:border-0"
                  >
                    <div class="w-2 h-2 rounded-full bg-prism-400 mt-2"></div>
                    <div class="flex-1">
                      <p class="text-white text-sm">{{ activity.description }}</p>
                      <p class="text-xs text-slate-400 mt-1">{{ formatDateTime(activity.created_at) }} by {{ activity.user?.name }}</p>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400">
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
      <p class="text-slate-300 mb-6">
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
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import {
  ArrowLeft, Edit, Trash2, TrendingUp, Flag,
  AlertTriangle, FileText, AlertCircle, Plus
} from 'lucide-vue-next'

const props = defineProps({
  project: Object,
})

const activeTab = ref('overview')
const showDeleteModal = ref(false)

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'risks', label: 'Risks' },
  { id: 'changes', label: 'Changes' },
  { id: 'activities', label: 'Activities' },
]

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
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
</script>
