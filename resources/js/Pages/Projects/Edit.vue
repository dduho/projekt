<template>
  <AppLayout :page-title="t('Edit Project')" :page-description="t('Edit project information')">
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
          <h1 :class="['text-3xl font-bold mb-2', textPrimary]">{{ t('Edit Project') }}</h1>
          <p :class="['text-sm', textPrimary]">{{ project.name }} ({{ project.project_code }})</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <GlassCard>
          <div class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h2 :class="['text-xl font-semibold mb-4', textPrimary]">{{ t('Basic Information') }}</h2>
              
              <div class="mb-4">
                <GlassInput
                  v-model="form.project_code"
                  :label="t('Project Code')"
                  :error="form.errors.project_code"
                  required
                />
              </div>

              <!-- Language Tabs -->
              <LanguageTabs v-model="currentLang" />

              <!-- Project Name (Translatable) -->
              <div class="mb-4">
                <GlassInput
                  v-if="currentLang === 'fr'"
                  v-model="form.name_fr"
                  :label="t('Project Name') + ' (🇫🇷)'"
                  :error="form.errors.name_fr"
                  required
                />
                <GlassInput
                  v-if="currentLang === 'en'"
                  v-model="form.name_en"
                  :label="t('Project Name') + ' (🇬🇧)'"
                  :error="form.errors.name_en"
                />
              </div>

              <!-- Description (Translatable) -->
              <GlassTextarea
                v-if="currentLang === 'fr'"
                v-model="form.description_fr"
                :label="t('Description') + ' (🇫🇷)'"
                :error="form.errors.description_fr"
                rows="4"
              />
              <GlassTextarea
                v-if="currentLang === 'en'"
                v-model="form.description_en"
                :label="t('Description') + ' (🇬🇧)'"
                :error="form.errors.description_en"
                rows="4"
              />
            </div>

            <!-- Category & Business Area -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassSelect
                v-model="form.category_id"
                :label="t('Category')"
                :options="categoryOptions"
                :error="form.errors.category_id"
                required
              />
              <GlassInput
                v-model="form.business_area"
                :label="t('Business Area')"
                :placeholder="t('e.g., Payment Services')"
                :error="form.errors.business_area"
              />
            </div>

            <!-- Priority & Owner -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GlassSelect
                v-model="form.priority"
                :label="t('Priority')"
                :options="priorityOptions"
                :error="form.errors.priority"
                required
              />
              <GlassSelect
                v-model="form.owner_id"
                :label="t('Owner')"
                :options="ownerOptions"
                :error="form.errors.owner_id"
              />
            </div>

            <!-- Status Section -->
            <div>
              <h2 :class="['text-xl font-semibold mb-4', textPrimary]">{{ t('Status') }}</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <GlassSelect
                  v-model="form.rag_status"
                  :label="t('RAG Status')"
                  :options="ragStatusOptions"
                  :error="form.errors.rag_status"
                  required
                />
                <GlassSelect
                  v-model="form.frs_status"
                  :label="t('FRS Status')"
                  :options="frsStatusOptions"
                  :error="form.errors.frs_status"
                  required
                />
                <GlassSelect
                  v-model="form.dev_status"
                  :label="t('Development Status')"
                  :options="devStatusOptions"
                  :error="form.errors.dev_status"
                  required
                />
              </div>
            </div>

            <!-- Progress -->
            <div>
              <div class="mb-4">
                <GlassInput
                  v-if="currentLang === 'fr'"
                  v-model="form.current_progress_fr"
                  :label="t('Current Progress') + ' (🇫🇷)'"
                  :placeholder="t('e.g., Integration phase completed')"
                  :error="form.errors.current_progress_fr"
                />
                <GlassInput
                  v-if="currentLang === 'en'"
                  v-model="form.current_progress_en"
                  :label="t('Current Progress') + ' (🇬🇧)'"
                  :placeholder="t('e.g., Integration phase completed')"
                  :error="form.errors.current_progress_en"
                />
              </div>
              <GlassInput
                v-model.number="form.completion_percent"
                :label="t('Completion')"
                type="number"
                min="0"
                max="100"
                :error="form.errors.completion_percent"
              />
            </div>

            <!-- Timeline -->
            <div>
              <h2 :class="['text-xl font-semibold mb-4', textPrimary]">{{ t('Timeline') }}</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <GlassInput
                  v-model="form.submission_date"
                  :label="t('Submission Date')"
                  type="date"
                  :error="form.errors.submission_date"
                />
                <GlassInput
                  v-model="form.target_date"
                  :label="t('Target Date')"
                  type="date"
                  :error="form.errors.target_date"
                />
                <GlassInput
                  v-model="form.planned_release"
                  :label="t('Planned Release')"
                  type="date"
                  :error="form.errors.planned_release"
                />
              </div>
            </div>

            <!-- Blockers -->
            <div>
              <GlassTextarea
                v-if="currentLang === 'fr'"
                v-model="form.blockers_fr"
                :label="t('Blockers') + ' (🇫🇷)'"
                :placeholder="t('Describe any blockers or issues')"
                :error="form.errors.blockers_fr"
                rows="3"
              />
              <GlassTextarea
                v-if="currentLang === 'en'"
                v-model="form.blockers_en"
                :label="t('Blockers') + ' (🇬🇧)'"
                :placeholder="t('Describe any blockers or issues')"
                :error="form.errors.blockers_en"
                rows="3"
              />
            </div>
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
            {{ t('Cancel') }}
          </GlassButton>
          <GlassButton
            variant="primary"
            type="submit"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
            <Save v-else class="w-5 h-5 mr-2" />
            {{ t('Save Changes') }}
          </GlassButton>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTranslation } from '@/Composables/useTranslation'
import { useTheme } from '@/Composables/useTheme'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import LanguageTabs from '@/Components/Glass/LanguageTabs.vue'
import { ArrowLeft, Save, Loader2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const { t, te } = useTranslation()
const { isDarkText } = useTheme()

const textPrimary = computed(() => isDarkText.value ? 'text-gray-900' : 'text-white')

const currentLang = ref('fr')

const props = defineProps({
  project: Object,
  categories: Array,
  users: Array,
})

const form = useForm({
  project_code: props.project.project_code,
  name_fr: props.project.name_translations?.fr || props.project.name || '',
  name_en: props.project.name_translations?.en || '',
  description_fr: props.project.description_translations?.fr || props.project.description || '',
  description_en: props.project.description_translations?.en || '',
  category_id: props.project.category_id,
  business_area: props.project.business_area || '',
  priority: props.project.priority,
  frs_status: props.project.frs_status,
  dev_status: props.project.dev_status,
  current_progress_fr: props.project.current_progress_translations?.fr || props.project.current_progress || '',
  current_progress_en: props.project.current_progress_translations?.en || '',
  blockers_fr: props.project.blockers_translations?.fr || props.project.blockers || '',
  blockers_en: props.project.blockers_translations?.en || '',
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
  { value: '', label: t('Select owner...') },
  ...(props.users?.map(user => ({ value: user.id, label: user.name })) || [])
])

const priorityOptions = computed(() => [
  { value: 'High', label: te('priority', 'High') },
  { value: 'Medium', label: te('priority', 'Medium') },
  { value: 'Low', label: te('priority', 'Low') },
])

const ragStatusOptions = computed(() => [
  { value: 'Green', label: te('rag_status', 'Green') },
  { value: 'Amber', label: te('rag_status', 'Amber') },
  { value: 'Red', label: te('rag_status', 'Red') },
])

const frsStatusOptions = computed(() => [
  { value: 'Draft', label: te('frs_status', 'Draft') },
  { value: 'Review', label: te('frs_status', 'Review') },
  { value: 'Signoff', label: te('frs_status', 'Signoff') },
])

const devStatusOptions = computed(() => [
  { value: 'Not Started', label: te('dev_status', 'Not Started') },
  { value: 'In Development', label: te('dev_status', 'In Development') },
  { value: 'Testing', label: te('dev_status', 'Testing') },
  { value: 'UAT', label: te('dev_status', 'UAT') },
  { value: 'Deployed', label: te('dev_status', 'Deployed') },
])

const submit = () => {
  form.put(route('projects.update', props.project.id), {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  })
}
</script>
