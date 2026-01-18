<template>
  <AppLayout page-title="Modifier Projet" page-description="Édition du projet">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <GlassButton
          variant="ghost"
          @click="$inertia.visit(route('projects.show', project.id))"
        >
          <ArrowLeft class="w-5 h-5" />
        </GlassButton>
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Edit Project</h1>
          <p class="text-slate-300">{{ project.name }} ({{ project.project_code }})</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <GlassCard>
          <div class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Basic Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <GlassInput
                  v-model="form.project_code"
                  label="Project Code"
                  :error="form.errors.project_code"
                  required
                />
                <GlassInput
                  v-model="form.name"
                  label="Project Name"
                  :error="form.errors.name"
                  required
                />
              </div>
            </div>

            <!-- Description -->
            <GlassTextarea
              v-model="form.description"
              label="Description"
              :error="form.errors.description"
              rows="4"
            />

            <!-- Category & Business Area -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassSelect
                v-model="form.category_id"
                label="Category"
                :options="categoryOptions"
                :error="form.errors.category_id"
                required
              />
              <GlassInput
                v-model="form.business_area"
                label="Business Area"
                placeholder="e.g., Payment Services"
                :error="form.errors.business_area"
              />
            </div>

            <!-- Priority & Owner -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassSelect
                v-model="form.priority"
                label="Priority"
                :options="priorityOptions"
                :error="form.errors.priority"
                required
              />
              <GlassSelect
                v-model="form.owner_id"
                label="Owner"
                :options="ownerOptions"
                :error="form.errors.owner_id"
              />
            </div>

            <!-- Status Section -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Status</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <GlassSelect
                  v-model="form.rag_status"
                  label="RAG Status"
                  :options="ragStatusOptions"
                  :error="form.errors.rag_status"
                  required
                />
                <GlassSelect
                  v-model="form.frs_status"
                  label="FRS Status"
                  :options="frsStatusOptions"
                  :error="form.errors.frs_status"
                  required
                />
                <GlassSelect
                  v-model="form.dev_status"
                  label="Development Status"
                  :options="devStatusOptions"
                  :error="form.errors.dev_status"
                  required
                />
              </div>
            </div>

            <!-- Progress -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassInput
                v-model="form.current_progress"
                label="Current Progress"
                placeholder="e.g., Integration phase completed"
                :error="form.errors.current_progress"
              />
              <GlassInput
                v-model.number="form.completion_percent"
                label="Completion (%)"
                type="number"
                min="0"
                max="100"
                :error="form.errors.completion_percent"
              />
            </div>

            <!-- Timeline -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Timeline</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <GlassInput
                  v-model="form.submission_date"
                  label="Submission Date"
                  type="date"
                  :error="form.errors.submission_date"
                />
                <GlassInput
                  v-model="form.target_date"
                  label="Target Date"
                  type="date"
                  :error="form.errors.target_date"
                />
                <GlassInput
                  v-model="form.planned_release"
                  label="Planned Release"
                  placeholder="e.g., v2.0"
                  :error="form.errors.planned_release"
                />
              </div>
            </div>

            <!-- Blockers -->
            <GlassTextarea
              v-model="form.blockers"
              label="Blockers (Optional)"
              placeholder="Describe any blockers or issues"
              :error="form.errors.blockers"
              rows="3"
            />
          </div>
        </GlassCard>

        <!-- Actions -->
        <div class="flex justify-end gap-4 mt-6">
          <GlassButton
            variant="secondary"
            type="button"
            @click="$inertia.visit(route('projects.show', project.id))"
            :disabled="form.processing"
          >
            Cancel
          </GlassButton>
          <GlassButton
            variant="primary"
            type="submit"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
            <Save v-else class="w-5 h-5 mr-2" />
            Save Changes
          </GlassButton>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import { ArrowLeft, Save, Loader2 } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps({
  project: Object,
  categories: Array,
  users: Array,
})

const form = useForm({
  project_code: props.project.project_code,
  name: props.project.name,
  description: props.project.description || '',
  category_id: props.project.category_id,
  business_area: props.project.business_area || '',
  priority: props.project.priority,
  frs_status: props.project.frs_status,
  dev_status: props.project.dev_status,
  current_progress: props.project.current_progress || '',
  blockers: props.project.blockers || '',
  owner_id: props.project.owner_id || '',
  planned_release: props.project.planned_release || '',
  target_date: props.project.target_date || '',
  submission_date: props.project.submission_date || '',
  rag_status: props.project.rag_status,
  completion_percent: props.project.completion_percent || 0,
})

const categoryOptions = computed(() =>
  props.categories?.map(cat => ({ value: cat.id, label: cat.name })) || []
)

const ownerOptions = computed(() => [
  { value: '', label: 'Select owner...' },
  ...(props.users?.map(user => ({ value: user.id, label: user.name })) || [])
])

const priorityOptions = [
  { value: 'High', label: 'High' },
  { value: 'Medium', label: 'Medium' },
  { value: 'Low', label: 'Low' },
]

const ragStatusOptions = [
  { value: 'Green', label: 'Green - On Track' },
  { value: 'Amber', label: 'Amber - At Risk' },
  { value: 'Red', label: 'Red - Critical' },
]

const frsStatusOptions = [
  { value: 'Draft', label: 'Draft' },
  { value: 'Review', label: 'Review' },
  { value: 'Signoff', label: 'Signoff' },
]

const devStatusOptions = [
  { value: 'Not Started', label: 'Not Started' },
  { value: 'In Development', label: 'In Development' },
  { value: 'Testing', label: 'Testing' },
  { value: 'UAT', label: 'UAT' },
  { value: 'Deployed', label: 'Deployed' },
]

const submit = () => {
  form.put(route('projects.update', props.project.id), {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  })
}
</script>
