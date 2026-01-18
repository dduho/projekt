<template>
  <AppLayout page-title="Categories" page-description="Gestion des catégories">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">Categories</h1>
          <p :class="isDarkText ? 'text-gray-600' : 'text-slate-300'">Manage project categories and their colors</p>
        </div>
        <GlassButton 
          variant="primary" 
          @click="showModal = true; editingCategory = null; resetForm()"
        >
          <Plus class="w-5 h-5 mr-2" />
          New Category
        </GlassButton>
      </div>

      <!-- Categories Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <GlassCard 
          v-for="category in categories" 
          :key="category.id"
          class="hover:scale-105 transition-transform"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div 
                class="w-6 h-6 rounded-full"
                :style="{ backgroundColor: category.color }"
              ></div>
              <div>
                <h3 :class="['text-lg font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ category.name }}</h3>
                <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-slate-400']">{{ category.projects_count || 0 }} projects</p>
              </div>
            </div>
            <div class="flex gap-1">
              <GlassButton
                variant="ghost"
                size="sm"
                @click="editCategory(category)"
              >
                <Edit class="w-4 h-4" />
              </GlassButton>
              <GlassButton
                variant="danger"
                size="sm"
                @click="deleteCategory(category)"
              >
                <Trash2 class="w-4 h-4" />
              </GlassButton>
            </div>
          </div>
          <p :class="['text-sm', isDarkText ? 'text-gray-700' : 'text-slate-300']">{{ category.description }}</p>
        </GlassCard>
      </div>

      <!-- Empty State -->
      <div v-if="categories.length === 0" class="text-center py-12">
        <GlassCard class="max-w-md mx-auto p-8">
          <Tag :class="['w-16 h-16 mx-auto mb-4', isDarkText ? 'text-gray-400' : 'text-slate-400']" />
          <h3 :class="['text-xl font-semibold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">No categories yet</h3>
          <p :class="['mb-6', isDarkText ? 'text-gray-600' : 'text-slate-300']">Start by creating your first category</p>
          <GlassButton 
            variant="primary" 
            @click="showModal = true; editingCategory = null; resetForm()"
          >
            <Plus class="w-5 h-5 mr-2" />
            Create Category
          </GlassButton>
        </GlassCard>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <GlassModal 
      :show="showModal"
      @close="showModal = false"
      :title="editingCategory ? 'Edit Category' : 'Create Category'"
    >
      <form @submit.prevent="submit">
        <div class="space-y-4">
          <GlassInput
            v-model="form.name"
            label="Name"
            placeholder="Category name"
            :error="form.errors.name"
            required
          />

          <GlassTextarea
            v-model="form.description"
            label="Description"
            placeholder="Category description"
            :error="form.errors.description"
            :rows="3"
          />

          <div>
            <label :class="['block text-sm font-medium mb-2', isDarkText ? 'text-gray-700' : 'text-slate-300']">
              Color
            </label>
            <div class="flex gap-3 items-center">
              <input
                type="color"
                v-model="form.color"
                :class="['w-16 h-10 rounded-lg cursor-pointer bg-transparent border-2', isDarkText ? 'border-gray-300' : 'border-white/20']"
              />
              <GlassInput
                v-model="form.color"
                placeholder="#667eea"
                :error="form.errors.color"
                class="flex-1"
              />
            </div>
            <p :class="['text-xs mt-1', isDarkText ? 'text-gray-600' : 'text-slate-400']">
              Preview: <span :style="{ color: form.color }">■ {{ form.color }}</span>
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <GlassButton 
            variant="secondary" 
            type="button"
            @click="showModal = false"
            :disabled="form.processing"
          >
            Cancel
          </GlassButton>
          <GlassButton 
            variant="primary" 
            type="submit"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            <Save v-else class="w-4 h-4 mr-2" />
            {{ editingCategory ? 'Update' : 'Create' }}
          </GlassButton>
        </div>
      </form>
    </GlassModal>

    <!-- Delete Confirmation -->
    <GlassModal :show="showDeleteModal" @close="showDeleteModal = false" title="Delete Category">
      <p :class="['mb-6', isDarkText ? 'text-gray-700' : 'text-slate-300']">
        Are you sure you want to delete "{{ deletingCategory?.name }}"? 
        {{ deletingCategory?.projects_count > 0 ? `This category has ${deletingCategory.projects_count} project(s).` : '' }}
      </p>
      <div class="flex justify-end gap-3">
        <GlassButton variant="secondary" @click="showDeleteModal = false">
          Cancel
        </GlassButton>
        <GlassButton variant="danger" @click="confirmDelete">
          Delete
        </GlassButton>
      </div>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassTextarea from '@/Components/Glass/GlassTextarea.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import { Plus, Edit, Trash2, Tag, Save, Loader2 } from 'lucide-vue-next'
import { useTheme } from '@/Composables/useTheme'

const { isDarkText } = useTheme()

const props = defineProps({
  categories: Array,
})

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingCategory = ref(null)
const deletingCategory = ref(null)

const form = useForm({
  name: '',
  description: '',
  color: '#667eea',
})

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.color = '#667eea'
  form.clearErrors()
}

const editCategory = (category) => {
  editingCategory.value = category
  form.name = category.name
  form.description = category.description
  form.color = category.color
  showModal.value = true
}

const submit = () => {
  if (editingCategory.value) {
    form.put(route('categories.update', editingCategory.value.id), {
      onSuccess: () => {
        showModal.value = false
        resetForm()
        editingCategory.value = null
      }
    })
  } else {
    form.post(route('categories.store'), {
      onSuccess: () => {
        showModal.value = false
        resetForm()
      }
    })
  }
}

const deleteCategory = (category) => {
  deletingCategory.value = category
  showDeleteModal.value = true
}

const confirmDelete = () => {
  router.delete(route('categories.destroy', deletingCategory.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      deletingCategory.value = null
    }
  })
}
</script>
