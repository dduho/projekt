<template>
  <AppLayout page-title="Nouveau Risque" page-description="Déclarer un nouveau risque">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <GlassButton 
          variant="ghost" 
          @click="$inertia.visit(route('risks.index'))"
        >
          <ArrowLeft class="w-5 h-5" />
        </GlassButton>
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Create New Risk</h1>
          <p class="text-slate-300">Identify and assess a potential risk</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <GlassCard>
          <div class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Risk Information</h2>
              <GlassInput
                v-model="form.title"
                label="Risk Title"
                placeholder="Brief description of the risk"
                :error="form.errors.title"
                required
              />
            </div>

            <GlassTextarea
              v-model="form.description"
              label="Detailed Description"
              placeholder="Describe the risk in detail"
              :error="form.errors.description"
              rows="4"
              required
            />

            <!-- Project Selection -->
            <GlassSelect
              v-model="form.project_id"
              label="Project"
              :options="projectOptions"
              :error="form.errors.project_id"
              required
            />

            <!-- Assessment -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Risk Assessment</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <GlassSelect
                  v-model.number="form.impact"
                  label="Impact"
                  :options="ratingOptions"
                  :error="form.errors.impact"
                  required
                />
                <GlassSelect
                  v-model.number="form.likelihood"
                  label="Likelihood"
                  :options="ratingOptions"
                  :error="form.errors.likelihood"
                  required
                />
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-2">
                    Risk Score
                  </label>
                  <div 
                    :class="[
                      'px-4 py-3 rounded-lg text-center font-bold text-2xl',
                      getRiskScoreClass(form.impact * form.likelihood)
                    ]"
                  >
                    {{ form.impact * form.likelihood }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Status & Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassSelect
                v-model="form.status"
                label="Status"
                :options="statusOptions"
                :error="form.errors.status"
                required
              />
              <GlassInput
                v-model="form.identified_date"
                label="Identified Date"
                type="date"
                :error="form.errors.identified_date"
                required
              />
            </div>

            <!-- Mitigation & Contingency -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Response Planning</h2>
              <div class="space-y-4">
                <GlassTextarea
                  v-model="form.mitigation"
                  label="Mitigation Strategy"
                  placeholder="How will you reduce the likelihood or impact?"
                  :error="form.errors.mitigation"
                  rows="3"
                />
                <GlassTextarea
                  v-model="form.contingency"
                  label="Contingency Plan"
                  placeholder="What will you do if the risk occurs?"
                  :error="form.errors.contingency"
                  rows="3"
                />
              </div>
            </div>
          </div>
        </GlassCard>

        <!-- Actions -->
        <div class="flex justify-end gap-4 mt-6">
          <GlassButton 
            variant="secondary" 
            type="button"
            @click="$inertia.visit(route('risks.index'))"
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
            Create Risk
          </GlassButton>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import { ArrowLeft, Save, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  projects: Array,
  project_id: [Number, String],
})

const form = useForm({
  title: '',
  description: '',
  project_id: props.project_id || '',
  impact: 3,
  likelihood: 3,
  status: 'Identified',
  identified_date: new Date().toISOString().split('T')[0],
  mitigation: '',
  contingency: '',
})

const projectOptions = computed(() => 
  props.projects.map(p => ({ value: p.id, label: `${p.code} - ${p.name}` }))
)

const ratingOptions = [
  { value: 1, label: '1 - Very Low' },
  { value: 2, label: '2 - Low' },
  { value: 3, label: '3 - Medium' },
  { value: 4, label: '4 - High' },
  { value: 5, label: '5 - Very High' },
]

const statusOptions = [
  { value: 'Identified', label: 'Identified' },
  { value: 'Assessing', label: 'Assessing' },
  { value: 'Mitigating', label: 'Mitigating' },
  { value: 'Monitoring', label: 'Monitoring' },
  { value: 'Closed', label: 'Closed' },
]

const getRiskScoreClass = (score) => {
  if (score >= 16) return 'bg-red-500/20 text-red-400 border border-red-500/30'
  if (score >= 10) return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'
  return 'bg-green-500/20 text-green-400 border border-green-500/30'
}

const submit = () => {
  form.post(route('risks.store'), {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  })
}
</script>
