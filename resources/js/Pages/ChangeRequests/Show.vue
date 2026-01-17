<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <GlassButton 
            variant="ghost" 
            @click="$inertia.visit(route('change-requests.index'))"
          >
            <ArrowLeft class="w-5 h-5" />
          </GlassButton>
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-3xl font-bold text-white">{{ changeRequest.title }}</h1>
              <StatusBadge :status="changeRequest.status" />
              <span :class="getPriorityClass(changeRequest.priority)">
                {{ changeRequest.priority }}
              </span>
            </div>
            <p class="text-slate-300">{{ changeRequest.project?.code }} - {{ changeRequest.project?.name }}</p>
          </div>
        </div>
        <div class="flex gap-2" v-if="changeRequest.status === 'Pending'">
          <GlassButton 
            variant="primary"
            @click="approve"
            v-if="can('approve change-requests')"
          >
            <Check class="w-4 h-4 mr-2" />
            Approve
          </GlassButton>
          <GlassButton 
            variant="danger"
            @click="reject"
            v-if="can('reject change-requests')"
          >
            <X class="w-4 h-4 mr-2" />
            Reject
          </GlassButton>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <GlassCard>
            <h3 class="text-lg font-semibold text-white mb-3">Description</h3>
            <p class="text-slate-300">{{ changeRequest.description }}</p>
          </GlassCard>

          <!-- Impact Analysis -->
          <GlassCard>
            <h3 class="text-lg font-semibold text-white mb-4">Impact Analysis</h3>
            <div class="grid grid-cols-2 gap-6">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <DollarSign class="w-5 h-5 text-green-400" />
                  <p class="text-slate-400">Cost Impact</p>
                </div>
                <p class="text-2xl font-bold text-white">{{ formatCurrency(changeRequest.cost_impact) }}</p>
              </div>
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <Calendar class="w-5 h-5 text-blue-400" />
                  <p class="text-slate-400">Schedule Impact</p>
                </div>
                <p class="text-2xl font-bold text-white">{{ changeRequest.schedule_impact }} days</p>
              </div>
            </div>
          </GlassCard>

          <!-- Justification -->
          <GlassCard v-if="changeRequest.justification">
            <h3 class="text-lg font-semibold text-white mb-3">Justification</h3>
            <p class="text-slate-300">{{ changeRequest.justification }}</p>
          </GlassCard>

          <!-- Approval Decision -->
          <GlassCard v-if="changeRequest.approval_notes">
            <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
              <MessageSquare class="w-5 h-5" />
              Approval Notes
            </h3>
            <p class="text-slate-300">{{ changeRequest.approval_notes }}</p>
            <div class="mt-3 pt-3 border-t border-white/10 text-sm text-slate-400">
              <p>Reviewed by: {{ changeRequest.approved_by?.name || 'N/A' }}</p>
              <p>Date: {{ formatDateTime(changeRequest.approved_at) }}</p>
            </div>
          </GlassCard>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Request Info -->
          <GlassCard>
            <h3 class="text-lg font-semibold text-white mb-4">Request Information</h3>
            <div class="space-y-3">
              <div>
                <p class="text-slate-400 text-sm">Requested By</p>
                <p class="text-white">{{ changeRequest.requested_by }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Requested Date</p>
                <p class="text-white">{{ formatDate(changeRequest.created_at) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Priority</p>
                <span :class="getPriorityClass(changeRequest.priority)">
                  {{ changeRequest.priority }}
                </span>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Status</p>
                <StatusBadge :status="changeRequest.status" />
              </div>
            </div>
          </GlassCard>

          <!-- Project Link -->
          <GlassCard>
            <h3 class="text-lg font-semibold text-white mb-3">Related Project</h3>
            <div 
              class="flex items-center gap-3 p-3 glass rounded-lg hover:bg-white/10 cursor-pointer transition"
              @click="$inertia.visit(route('projects.show', changeRequest.project_id))"
            >
              <div 
                class="w-3 h-3 rounded-full" 
                :style="{ backgroundColor: changeRequest.project?.category?.color }"
              ></div>
              <div class="flex-1">
                <p class="text-white font-semibold">{{ changeRequest.project?.name }}</p>
                <p class="text-sm text-slate-400">{{ changeRequest.project?.code }}</p>
              </div>
              <ExternalLink class="w-4 h-4 text-slate-400" />
            </div>
          </GlassCard>

          <!-- Actions -->
          <GlassCard v-if="can('edit change-requests') && changeRequest.status === 'Pending'">
            <h3 class="text-lg font-semibold text-white mb-3">Actions</h3>
            <div class="space-y-2">
              <GlassButton 
                variant="secondary"
                class="w-full"
                @click="$inertia.visit(route('change-requests.edit', changeRequest.id))"
              >
                <Edit class="w-4 h-4 mr-2" />
                Edit Request
              </GlassButton>
              <GlassButton 
                variant="danger"
                class="w-full"
                @click="confirmDelete"
                v-if="can('delete change-requests')"
              >
                <Trash2 class="w-4 h-4 mr-2" />
                Delete Request
              </GlassButton>
            </div>
          </GlassCard>
        </div>
      </div>
    </div>

    <!-- Approval/Rejection Modal -->
    <GlassModal v-model="showDecisionModal" :title="decisionType === 'approve' ? 'Approve Change Request' : 'Reject Change Request'">
      <GlassTextarea
        v-model="decisionNotes"
        label="Notes"
        placeholder="Add notes about your decision..."
        rows="4"
      />
      <div class="flex justify-end gap-3 mt-4">
        <GlassButton variant="secondary" @click="showDecisionModal = false">
          Cancel
        </GlassButton>
        <GlassButton 
          :variant="decisionType === 'approve' ? 'primary' : 'danger'"
          @click="submitDecision"
        >
          {{ decisionType === 'approve' ? 'Approve' : 'Reject' }}
        </GlassButton>
      </div>
    </GlassModal>

    <!-- Delete Confirmation Modal -->
    <GlassModal v-model="showDeleteModal" title="Delete Change Request">
      <p class="text-slate-300 mb-6">
        Are you sure you want to delete this change request? This action cannot be undone.
      </p>
      <div class="flex justify-end gap-3">
        <GlassButton variant="secondary" @click="showDeleteModal = false">
          Cancel
        </GlassButton>
        <GlassButton variant="danger" @click="deleteChangeRequest">
          Delete
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
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import { 
  ArrowLeft, Check, X, DollarSign, Calendar, MessageSquare, 
  ExternalLink, Edit, Trash2 
} from 'lucide-vue-next'

const props = defineProps({
  changeRequest: Object,
})

const showDecisionModal = ref(false)
const showDeleteModal = ref(false)
const decisionType = ref('approve')
const decisionNotes = ref('')

const approve = () => {
  decisionType.value = 'approve'
  decisionNotes.value = ''
  showDecisionModal.value = true
}

const reject = () => {
  decisionType.value = 'reject'
  decisionNotes.value = ''
  showDecisionModal.value = true
}

const submitDecision = () => {
  const route_name = decisionType.value === 'approve' 
    ? 'api.change-requests.approve' 
    : 'api.change-requests.reject'
    
  router.post(route(route_name, props.changeRequest.id), {
    notes: decisionNotes.value
  }, {
    onSuccess: () => {
      showDecisionModal.value = false
    }
  })
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteChangeRequest = () => {
  router.delete(route('change-requests.destroy', props.changeRequest.id))
}

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const formatDateTime = (date) => {
  return date ? new Date(date).toLocaleString('fr-FR') : '-'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
  }).format(amount || 0)
}

const getPriorityClass = (priority) => {
  const classes = {
    Low: 'px-2 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400',
    Medium: 'px-2 py-1 rounded-full text-xs font-semibold bg-yellow-500/20 text-yellow-400',
    High: 'px-2 py-1 rounded-full text-xs font-semibold bg-orange-500/20 text-orange-400',
    Critical: 'px-2 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400',
  }
  return classes[priority] || ''
}
</script>
