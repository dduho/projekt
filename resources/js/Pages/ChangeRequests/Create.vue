<template>
  <AppLayout page-title="Nouvelle Demande" page-description="Créer une demande de changement">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <GlassButton
          variant="ghost"
          @click="$inertia.visit(route('change-requests.index'))"
        >
          <ArrowLeft class="w-5 h-5" />
        </GlassButton>
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Create Change Request</h1>
          <p class="text-slate-300">Submit a new change request for approval</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <GlassCard>
          <div class="space-y-6">
            <!-- Project Selection -->
            <div>
              <h2 class="text-xl font-semibold text-white mb-4">Change Details</h2>
              <GlassSelect
                v-model="form.project_id"
                label="Project"
                :options="projectOptions"
                :error="form.errors.project_id"
                required
              />
            </div>

            <!-- Change Type -->
            <GlassSelect
              v-model="form.change_type"
              label="Change Type"
              :options="changeTypeOptions"
              :error="form.errors.change_type"
              required
            />

            <!-- Description -->
            <GlassTextarea
              v-model="form.description"
              label="Description"
              placeholder="Describe the change request in detail"
              :error="form.errors.description"
              rows="4"
              required
            />
          </div>
        </GlassCard>

        <!-- Actions -->
        <div class="flex justify-end gap-4 mt-6">
          <GlassButton
            variant="secondary"
            type="button"
            @click="$inertia.visit(route('change-requests.index'))"
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
            Submit Change Request
          </GlassButton>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import { ArrowLeft, Save, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  projects: Array,
  project_id: [Number, String],
})

const form = useForm({
  project_id: props.project_id || '',
  change_type: 'Scope',
  description: '',
})

const projectOptions = computed(() =>
  props.projects?.map(p => ({ value: p.id, label: `${p.project_code} - ${p.name}` })) || []
)

const changeTypeOptions = [
  { value: 'Scope', label: 'Scope Change' },
  { value: 'Schedule', label: 'Schedule Change' },
  { value: 'Budget', label: 'Budget Change' },
  { value: 'Resource', label: 'Resource Change' },
]

const submit = () => {
  form.post(route('change-requests.store'), {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  })
}
</script>
