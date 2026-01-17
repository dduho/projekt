<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Users Management</h1>
          <p class="text-slate-300">Manage users, roles and permissions</p>
        </div>
        <GlassButton 
          variant="primary" 
          @click="showModal = true; editingUser = null; resetForm()"
        >
          <Plus class="w-5 h-5 mr-2" />
          New User
        </GlassButton>
      </div>

      <!-- Users Table -->
      <GlassCard>
        <DataTable
          :columns="columns"
          :data="users.data"
          :loading="false"
        >
          <template #cell-name="{ row }">
            <div>
              <p class="font-semibold text-white">{{ row.name }}</p>
              <p class="text-sm text-slate-400">{{ row.email }}</p>
            </div>
          </template>

          <template #cell-roles="{ row }">
            <div class="flex gap-1">
              <span 
                v-for="role in row.roles" 
                :key="role.name"
                :class="getRoleClass(role.name)"
              >
                {{ role.name }}
              </span>
            </div>
          </template>

          <template #cell-created_at="{ row }">
            <span class="text-slate-300">{{ formatDate(row.created_at) }}</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex gap-2">
              <GlassButton
                variant="ghost"
                size="sm"
                @click="editUser(row)"
              >
                <Edit class="w-4 h-4" />
              </GlassButton>
              <GlassButton
                variant="danger"
                size="sm"
                @click="deleteUser(row)"
                :disabled="row.id === currentUserId"
              >
                <Trash2 class="w-4 h-4" />
              </GlassButton>
            </div>
          </template>
        </DataTable>
      </GlassCard>

      <!-- Pagination -->
      <div class="flex justify-center" v-if="users.last_page > 1">
        <div class="flex gap-2">
          <GlassButton 
            variant="secondary" 
            :disabled="!users.prev_page_url"
            @click="changePage(users.current_page - 1)"
          >
            Previous
          </GlassButton>
          <div class="flex items-center gap-2 px-4">
            <span class="text-white">Page {{ users.current_page }} of {{ users.last_page }}</span>
          </div>
          <GlassButton 
            variant="secondary" 
            :disabled="!users.next_page_url"
            @click="changePage(users.current_page + 1)"
          >
            Next
          </GlassButton>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <GlassModal 
      v-model="showModal" 
      :title="editingUser ? 'Edit User' : 'Create User'"
      size="lg"
    >
      <form @submit.prevent="submit">
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <GlassInput
              v-model="form.name"
              label="Name"
              placeholder="Full name"
              :error="form.errors.name"
              required
            />

            <GlassInput
              v-model="form.email"
              label="Email"
              type="email"
              placeholder="user@example.com"
              :error="form.errors.email"
              required
            />
          </div>

          <GlassInput
            v-if="!editingUser"
            v-model="form.password"
            label="Password"
            type="password"
            placeholder="••••••••"
            :error="form.errors.password"
            :required="!editingUser"
          />

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">
              Role
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label 
                v-for="role in availableRoles" 
                :key="role.value"
                class="flex items-center gap-2 p-3 glass rounded-lg cursor-pointer hover:bg-white/10 transition"
              >
                <input
                  type="radio"
                  v-model="form.role"
                  :value="role.value"
                  class="w-4 h-4 text-prism-500 bg-slate-700 border-slate-600"
                />
                <div>
                  <p class="text-white font-semibold">{{ role.label }}</p>
                  <p class="text-xs text-slate-400">{{ role.description }}</p>
                </div>
              </label>
            </div>
            <p v-if="form.errors.role" class="text-red-400 text-sm mt-1">{{ form.errors.role }}</p>
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
            {{ editingUser ? 'Update' : 'Create' }}
          </GlassButton>
        </div>
      </form>
    </GlassModal>

    <!-- Delete Confirmation -->
    <GlassModal v-model="showDeleteModal" title="Delete User">
      <p class="text-slate-300 mb-6">
        Are you sure you want to delete "{{ deletingUser?.name }}"? This action cannot be undone.
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
import { ref, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import DataTable from '@/Components/DataTable.vue'
import { Plus, Edit, Trash2, Save, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  users: Object,
})

const page = usePage()
const currentUserId = computed(() => page.props.auth.user?.id)

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref(null)
const deletingUser = ref(null)

const columns = [
  { key: 'name', label: 'User', sortable: true },
  { key: 'roles', label: 'Role', sortable: false },
  { key: 'created_at', label: 'Created', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false },
]

const availableRoles = [
  { 
    value: 'admin', 
    label: 'Administrator', 
    description: 'Full system access' 
  },
  { 
    value: 'manager', 
    label: 'Manager', 
    description: 'Manage projects and teams' 
  },
  { 
    value: 'user', 
    label: 'User', 
    description: 'View and create content' 
  },
  { 
    value: 'guest', 
    label: 'Guest', 
    description: 'Read-only access' 
  },
]

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: 'user',
})

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.password = ''
  form.role = 'user'
  form.clearErrors()
}

const editUser = (user) => {
  editingUser.value = user
  form.name = user.name
  form.email = user.email
  form.password = ''
  form.role = user.roles?.[0]?.name || 'user'
  showModal.value = true
}

const submit = () => {
  if (editingUser.value) {
    form.put(route('users.update', editingUser.value.id), {
      onSuccess: () => {
        showModal.value = false
        resetForm()
        editingUser.value = null
      }
    })
  } else {
    form.post(route('users.store'), {
      onSuccess: () => {
        showModal.value = false
        resetForm()
      }
    })
  }
}

const deleteUser = (user) => {
  if (user.id === currentUserId.value) {
    alert('You cannot delete your own account')
    return
  }
  deletingUser.value = user
  showDeleteModal.value = true
}

const confirmDelete = () => {
  router.delete(route('users.destroy', deletingUser.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      deletingUser.value = null
    }
  })
}

const changePage = (page) => {
  router.get(route('users.index', { page }), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const getRoleClass = (role) => {
  const classes = {
    admin: 'px-2 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-400',
    manager: 'px-2 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-400',
    user: 'px-2 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400',
    guest: 'px-2 py-1 rounded-full text-xs font-semibold bg-slate-500/20 text-slate-400',
  }
  return classes[role] || 'px-2 py-1 rounded-full text-xs font-semibold bg-slate-500/20 text-slate-400'
}
</script>
