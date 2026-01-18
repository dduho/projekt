<template>
    <div class="space-y-4">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-bold text-gray-900">{{ t('project_timeline') }}</h3>
            <p class="text-sm text-gray-500">{{ t('phases_progress') }}</p>
        </div>

        <!-- Chart -->
        <div class="overflow-x-auto">
            <div class="min-w-full bg-white rounded-lg border border-gray-200 p-4">
                <!-- Timeline Header -->
                <div class="mb-4">
                    <div class="flex justify-between items-end text-xs text-gray-500 px-32">
                        <span>{{ formatDate(projectDates.start) }}</span>
                        <span>{{ formatDate(projectDates.mid) }}</span>
                        <span>{{ formatDate(projectDates.end) }}</span>
                    </div>
                    <div class="flex h-1 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded">
                        <div class="flex-1"></div>
                    </div>
                </div>

                <!-- Phases -->
                <div class="space-y-3">
                    <div v-for="phase in phases" :key="phase.id" class="space-y-1">
                        <!-- Phase Name & Stats -->
                        <div class="flex items-center gap-2">
                            <div class="w-32">
                                <p class="font-medium text-sm text-gray-900">{{ phase.phase }}</p>
                                <p class="text-xs text-gray-500">{{ phase.status }}</p>
                            </div>
                            <div class="flex-1 flex items-center gap-2">
                                <!-- Bar -->
                                <div class="relative flex-1 h-8 bg-gray-100 rounded">
                                    <div
                                        :style="phaseBarStyle(phase)"
                                        :class="[
                                            'absolute h-full rounded transition-all duration-300 flex items-center justify-center',
                                            getPhaseColor(phase.status)
                                        ]"
                                    >
                                        <span v-if="showProgress(phase)" class="text-xs font-bold text-white drop-shadow">
                                            {{ phaseProgress(phase) }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="w-48 text-right text-xs text-gray-500">
                                    <span v-if="phase.start_date">{{ formatDate(phase.start_date) }}</span>
                                    <span v-if="phase.start_date && phase.end_date"> → </span>
                                    <span v-if="phase.end_date">{{ formatDate(phase.end_date) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div v-if="phase.status === 'In Progress'" class="ml-32 h-1 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-blue-500 transition-all duration-300"
                                :style="{ width: phaseProgress(phase) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex flex-wrap gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-gray-400 rounded"></div>
                        <span class="text-xs text-gray-600">{{ t('pending') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded"></div>
                        <span class="text-xs text-gray-600">{{ t('in_progress') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded"></div>
                        <span class="text-xs text-gray-600">{{ t('completed') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div v-if="summary" class="grid grid-cols-3 gap-4">
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <p class="text-sm text-blue-600 font-medium">{{ t('in_progress') }}</p>
                <p class="text-2xl font-bold text-blue-700">{{ summary.inProgress }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-sm text-green-600 font-medium">{{ t('completed') }}</p>
                <p class="text-2xl font-bold text-green-700">{{ summary.completed }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-sm text-gray-600 font-medium">{{ t('pending') }}</p>
                <p class="text-2xl font-bold text-gray-700">{{ summary.pending }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    phases: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const t = (key) => {
    return page.props.translations?.[key] || key
}

const phases = ref(props.phases)

// Calculate project date range
const projectDates = computed(() => {
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 6, 0)
    const mid = new Date((start.getTime() + end.getTime()) / 2)

    return { start, mid, end }
})

// Calculate summary
const summary = computed(() => ({
    pending: phases.value.filter(p => p.status === 'Pending').length,
    inProgress: phases.value.filter(p => p.status === 'In Progress').length,
    completed: phases.value.filter(p => p.status === 'Completed').length,
}))

const formatDate = (date) => {
    if (!date) return ''
    const d = new Date(date)
    return d.toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' })
}

const phaseProgress = (phase) => {
    // Simulated progress based on status
    const progressMap = {
        'Pending': 0,
        'In Progress': 50,
        'Completed': 100,
    }
    return progressMap[phase.status] || 0
}

const showProgress = (phase) => phaseProgress(phase) > 0

const phaseBarStyle = (phase) => {
    const progress = phaseProgress(phase)
    return {
        width: progress + '%',
    }
}

const getPhaseColor = (status) => ({
    'Pending': 'bg-gray-400',
    'In Progress': 'bg-blue-500',
    'Completed': 'bg-green-500',
}[status] || 'bg-gray-400')
</script>
