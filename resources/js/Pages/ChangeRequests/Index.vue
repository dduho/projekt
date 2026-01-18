<template>
  <AppLayout :page-title="t('Change Requests')" :page-description="t('Manage project change requests and approvals')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900 font-bold' : 'text-white font-bold']">{{ t('Change Requests') }}</h1>
          <p :class="[isDarkText ? 'text-gray-700' : 'text-gray-200']">{{ t('Manage project change requests and approvals') }}</p>
        </div>
        <GlassButton
          variant="primary"
          @click="$inertia.visit(route('change-requests.create'))"
          v-if="can('create change-requests')"
        >
          <Plus class="w-5 h-5 mr-2" />
          {{ t('New Change Request') }}
        </GlassButton>
      </div>

      <!-- Filters -->
      <GlassCard>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <GlassInput
            v-model="filters.search"
            :placeholder="t('Search change requests...')"
            :icon="Search"
            @input="debouncedSearch"
          />
          <GlassSelect
            v-model="filters.status"
            :options="statusOptions"
            :placeholder="t('All Statuses')"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.priority"
            :options="priorityOptions"
            :placeholder="t('All Priorities')"
            @change="applyFilters"
          />
          <GlassSelect
            v-model="filters.project"
            :options="projectOptions"
            :placeholder="t('All Projects')"
            @change="applyFilters"
          />
          <GlassButton variant="secondary" @click="resetFilters">
            <X class="w-4 h-4 mr-2" />
            {{ t('Reset') }}
          </GlassButton>
        </div>
      </GlassCard>

      <!-- Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <GlassCard>
          <div class="text-center">
            <p :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ stats.total }}</p>
            <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Total') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-yellow-400 mb-2">{{ stats.pending }}</p>
            <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Pending') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-green-400 mb-2">{{ stats.approved }}</p>
            <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Approved') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-red-400 mb-2">{{ stats.rejected }}</p>
            <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Rejected') }}</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p :class="['text-2xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ formatCurrency(stats.totalCost) }}</p>
            <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ t('Total Cost Impact') }}</p>
          </div>
        </GlassCard>
      </div>

      <!-- Change Requests Table -->
      <GlassCard>
        <DataTable
          :columns="columns"
          :data="changeRequests.data"
          :loading="false"
          @row-click="viewChange"
        >
          <template #cell-title="{ row }">
            <div>
              <p :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ row.title }}</p>
              <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ row.project?.code }}</p>
            </div>
          </template>

          <template #cell-priority="{ row }">
            <span :class="getPriorityClass(row.priority)">
              {{ te('priority', row.priority) }}
            </span>
          </template>

          <template #cell-cost_impact="{ row }">
            <span :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">{{ formatCurrency(row.cost_impact) }}</span>
          </template>

          <template #cell-schedule_impact="{ row }">
            <span :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ row.schedule_impact }} {{ t('days') }}</span>
          </template>

          <template #cell-status="{ row }">
            <StatusBadge :status="row.status" size="sm" />
          </template>

          <template #cell-actions="{ row }">
            <div class="flex gap-2" @click.stop>
              <GlassButton
                variant="ghost"
                size="sm"
                @click="viewChange(row)"
                v-if="can('view change-requests')"
              >
                <Eye class="w-4 h-4" />
              </GlassButton>
              <GlassButton
                variant="primary"
                size="sm"
                @click="approveChange(row)"
                v-if="can('approve change-requests') && row.status === 'Pending'"
              >
                <Check class="w-4 h-4" />
              </GlassButton>
              <GlassButton
                variant="danger"
                size="sm"
                @click="rejectChange(row)"
                v-if="can('reject change-requests') && row.status === 'Pending'"
              >
                <X class="w-4 h-4" />
              </GlassButton>
            </div>
          </template>
        </DataTable>
      </GlassCard>

      <!-- Pagination -->
      <div class="flex justify-center" v-if="changeRequests.last_page > 1">
        <div class="flex gap-2">
          <GlassButton
            variant="secondary"
            :disabled="!changeRequests.prev_page_url"
            @click="changePage(changeRequests.current_page - 1)"
          >
            {{ t('Previous') }}
          </GlassButton>
          <div class="flex items-center gap-2 px-4">
            <span :class="[isDarkText ? 'text-gray-900' : 'text-white']">{{ t('Page :current of :total', { current: changeRequests.current_page, total: changeRequests.last_page }) }}</span>
          </div>
          <GlassButton
            variant="secondary"
            :disabled="!changeRequests.next_page_url"
            @click="changePage(changeRequests.current_page + 1)"
          >
            {{ t('Next') }}
          </GlassButton>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="changeRequests.data.length === 0" class="text-center py-12">
        <GlassCard class="max-w-md mx-auto p-8">
          <FileText :class="['w-16 h-16 mx-auto mb-4', isDarkText ? 'text-gray-600' : 'text-gray-400']" />
          <h3 :class="['text-xl font-semibold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">{{ t('No change requests found') }}</h3>
          <p :class="['mb-6', isDarkText ? 'text-gray-700' : 'text-gray-200']">
            {{ filters.search || filters.status ? t('Try adjusting your filters') : t('Start by creating your first change request') }}
          </p>
          <GlassButton
            variant="primary"
            @click="$inertia.visit(route('change-requests.create'))"
            v-if="can('create change-requests')"
          >
            <Plus class="w-5 h-5 mr-2" />
            {{ t('Create Change Request') }}
          </GlassButton>
        </GlassCard>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import GlassSelect from '@/Components/Glass/GlassSelect.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import DataTable from '@/Components/DataTable.vue'
import { Plus, Search, X, FileText, Eye, Check } from 'lucide-vue-next'

const { isDarkText } = useTheme()
const { t, te, formatCurrency: formatCurrencyFn, locale } = useTranslation()

const props = defineProps({
  changeRequests: Object,
  projects: Array,
  stats: Object,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  priority: props.filters?.priority || '',
  project: props.filters?.project || '',
})

const columns = computed(() => [
  { key: 'title', label: t('Title'), sortable: true },
  { key: 'priority', label: t('Priority'), sortable: true },
  { key: 'cost_impact', label: t('Cost Impact'), sortable: true },
  { key: 'schedule_impact', label: t('Schedule Impact'), sortable: true },
  { key: 'status', label: t('Status'), sortable: true },
  { key: 'actions', label: t('Actions'), sortable: false },
])

const statusOptions = computed(() => [
  { value: '', label: t('All Statuses') },
  { value: 'Pending', label: t('Pending') },
  { value: 'Approved', label: t('Approved') },
  { value: 'Rejected', label: t('Rejected') },
  { value: 'Implemented', label: t('Implemented') },
])

const priorityOptions = computed(() => [
  { value: '', label: t('All Priorities') },
  { value: 'Low', label: t('Low') },
  { value: 'Medium', label: t('Medium') },
  { value: 'High', label: t('High') },
  { value: 'Critical', label: t('Critical') },
])

const projectOptions = computed(() => [
  { value: '', label: t('All Projects') },
  ...props.projects.map(p => ({ value: p.id, label: `${p.code} - ${p.name}` }))
])

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  router.get(route('change-requests.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  filters.value = { search: '', status: '', priority: '', project: '' }
  applyFilters()
}

const changePage = (page) => {
  router.get(route('change-requests.index', { ...filters.value, page }), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const viewChange = (row) => {
  router.visit(route('change-requests.show', row.id))
}

const approveChange = (row) => {
  if (confirm(t('Are you sure you want to approve this change request?'))) {
    router.post(route('api.change-requests.approve', row.id), {}, {
      onSuccess: () => applyFilters()
    })
  }
}

const rejectChange = (row) => {
  if (confirm(t('Are you sure you want to reject this change request?'))) {
    router.post(route('api.change-requests.reject', row.id), {}, {
      onSuccess: () => applyFilters()
    })
  }
}

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
}

const formatCurrency = (amount) => {
  const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
  return new Intl.NumberFormat(localeCode, {
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
