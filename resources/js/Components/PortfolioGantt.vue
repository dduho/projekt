<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">{{ t('Portfolio Timeline') }}</h3>
                <p class="text-sm text-gray-500">{{ t('All projects timeline') }}</p>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                    <span class="text-xs text-gray-600">{{ t('Deployed') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded"></div>
                    <span class="text-xs text-gray-600">{{ t('In Progress') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-amber-500 rounded"></div>
                    <span class="text-xs text-gray-600">{{ t('Planning') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gray-400 rounded"></div>
                    <span class="text-xs text-gray-600">{{ t('Not Started') }}</span>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="overflow-x-auto">
            <div class="min-w-full bg-white rounded-lg border border-gray-200 p-4">
                <!-- Timeline Header (Months) -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs font-semibold text-gray-500 px-32">
                        <span v-for="month in timelineMonths" :key="month">{{ month }}</span>
                    </div>
                    <div class="flex h-1 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded"></div>
                </div>

                <!-- Projects -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div v-for="project in sortedProjects" :key="project.id" class="group">
                        <!-- Project Row -->
                        <div class="flex items-center gap-2">
                            <!-- Project Name -->
                            <div class="w-48 flex-shrink-0">
                                <p class="font-semibold text-sm text-gray-900 truncate">{{ project.name }}</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-xs text-gray-500">{{ project.project_code }}</p>
                                    <div 
                                        class="w-2 h-2 rounded-full"
                                        :class="getRagColor(project.calculated_rag_status || project.rag_status)"
                                    ></div>
                                </div>
                            </div>

                            <!-- Timeline Bar -->
                            <div class="flex-1 relative h-8 bg-gray-50 rounded">
                                <div
                                    :style="getProjectBarStyle(project)"
                                    :class="[
                                        'absolute h-full rounded transition-all duration-300 flex items-center px-2',
                                        getStatusColor(project.dev_status),
                                        'group-hover:shadow-md'
                                    ]"
                                >
                                    <span class="text-xs font-bold text-white truncate">
                                        {{ project.completion_percent || 0 }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="w-32 flex-shrink-0 text-right text-xs text-gray-500">
                                <span v-if="project.submission_date">{{ formatDate(project.submission_date) }}</span>
                                <span v-if="project.submission_date && project.target_date"> → </span>
                                <span v-if="project.target_date">{{ formatDate(project.target_date) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!sortedProjects.length" class="text-center py-8 text-gray-500">
                        {{ t('No projects') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                <p class="text-xs text-green-600 font-medium">{{ t('Deployed') }}</p>
                <p class="text-2xl font-bold text-green-700">{{ stats.deployed }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                <p class="text-xs text-blue-600 font-medium">{{ t('In Progress') }}</p>
                <p class="text-2xl font-bold text-blue-700">{{ stats.inProgress }}</p>
            </div>
            <div class="bg-amber-50 rounded-lg p-3 border border-amber-200">
                <p class="text-xs text-amber-600 font-medium">{{ t('Planning') }}</p>
                <p class="text-2xl font-bold text-amber-700">{{ stats.planning }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <p class="text-xs text-gray-600 font-medium">{{ t('Total') }}</p>
                <p class="text-2xl font-bold text-gray-700">{{ stats.total }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const t = (key) => {
    return page.props.translations?.[key] || key
}

// Generate timeline months (6 months)
const timelineMonths = computed(() => {
    const months = []
    const now = new Date()
    for (let i = -2; i <= 3; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() + i, 1)
        months.push(date.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' }))
    }
    return months
})

// Sort projects by status priority
const sortedProjects = computed(() => {
    const statusPriority = {
        'Deployed': 1,
        'In Development': 2,
        'Testing': 2,
        'UAT': 2,
        'In Progress': 2,
        'Planning': 3,
        'Not Started': 4,
    }
    
    return [...props.projects].sort((a, b) => {
        const priorityA = statusPriority[a.dev_status] || 5
        const priorityB = statusPriority[b.dev_status] || 5
        return priorityA - priorityB
    })
})

// Calculate stats
const stats = computed(() => ({
    deployed: props.projects.filter(p => p.dev_status === 'Deployed').length,
    inProgress: props.projects.filter(p => ['In Development', 'Testing', 'UAT'].includes(p.dev_status)).length,
    planning: props.projects.filter(p => ['Planning', 'FRS'].includes(p.dev_status)).length,
    total: props.projects.length,
}))

const formatDate = (date) => {
    if (!date) return ''
    const d = new Date(date)
    return d.toLocaleDateString('fr-FR', { month: 'short', day: 'numeric', year: 'numeric' })
}

const getProjectBarStyle = (project) => {
    // Calculate position based on dates
    const now = new Date()
    const sixMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 2, 1)
    const sixMonthsLater = new Date(now.getFullYear(), now.getMonth() + 4, 0)
    
    const startDate = project.submission_date ? new Date(project.submission_date) : sixMonthsAgo
    const endDate = project.target_date ? new Date(project.target_date) : sixMonthsLater
    
    const totalDays = (sixMonthsLater - sixMonthsAgo) / (1000 * 60 * 60 * 24)
    const startOffset = Math.max(0, (startDate - sixMonthsAgo) / (1000 * 60 * 60 * 24))
    const duration = (endDate - startDate) / (1000 * 60 * 60 * 24)
    
    const left = (startOffset / totalDays) * 100
    const width = Math.min((duration / totalDays) * 100, 100 - left)
    
    return {
        left: `${Math.max(0, Math.min(left, 95))}%`,
        width: `${Math.max(5, width)}%`,
    }
}

const getStatusColor = (status) => ({
    'Deployed': 'bg-green-500',
    'Configuration Done': 'bg-green-500',
    'In Development': 'bg-blue-500',
    'Testing': 'bg-blue-500',
    'UAT': 'bg-blue-500',
    'In Progress': 'bg-blue-500',
    'FRS': 'bg-amber-500',
    'Planning': 'bg-amber-500',
    'Not Started': 'bg-gray-400',
    'Waiting': 'bg-gray-400',
}[status] || 'bg-gray-400')

const getRagColor = (rag) => ({
    'Green': 'bg-green-500',
    'Amber': 'bg-amber-500',
    'Red': 'bg-red-500',
}[rag] || 'bg-gray-400')
</script>
