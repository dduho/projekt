<template>
    <AppLayout 
        page-title="Dashboard"
        page-description="Vue d'ensemble de vos projets"
    >
        <!-- Alerts Banner -->
        <div v-if="alerts?.length" class="mb-6 space-y-3">
            <div 
                v-for="alert in alerts" 
                :key="alert.title"
                :class="[
                    'p-4 rounded-xl flex items-center justify-between',
                    getAlertClass(alert.type)
                ]"
            >
                <div class="flex items-center gap-3">
                    <component :is="getAlertIcon(alert.icon)" class="w-5 h-5" />
                    <div>
                        <p class="font-semibold">{{ alert.title }}</p>
                        <p class="text-sm opacity-80">{{ alert.message }}</p>
                    </div>
                </div>
                <span class="text-2xl font-bold">{{ alert.count }}</span>
            </div>
        </div>

        <!-- Health Score & Main KPIs -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Portfolio Health Score -->
            <GlassCard animated class="lg:col-span-1">
                <div class="text-center">
                    <p :class="['text-sm mb-2', textMuted]">Portfolio Health</p>
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle 
                                cx="64" cy="64" r="56" 
                                stroke-width="8" 
                                :stroke="isDarkText ? '#e5e7eb' : 'rgba(255,255,255,0.1)'"
                                fill="none"
                            />
                            <circle 
                                cx="64" cy="64" r="56" 
                                stroke-width="8" 
                                :stroke="getHealthColor(healthMetrics?.health_score)"
                                fill="none"
                                :stroke-dasharray="352"
                                :stroke-dashoffset="352 - (352 * (healthMetrics?.health_score || 0) / 100)"
                                stroke-linecap="round"
                                class="transition-all duration-1000"
                            />
                        </svg>
                        <span :class="['absolute text-3xl font-bold', textPrimary]">
                            {{ healthMetrics?.health_score || 0 }}%
                        </span>
                    </div>
                    <p :class="['text-xs mt-2', textMuted]">
                        {{ getHealthLabel(healthMetrics?.health_score) }}
                    </p>
                </div>
            </GlassCard>

            <!-- Quick Stats Grid -->
            <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-4">
                <GlassCard animated v-for="stat in quickStats" :key="stat.label">
                    <div class="flex items-center gap-3">
                        <div :class="['p-2 rounded-lg', stat.bg]">
                            <component :is="stat.icon" class="w-5 h-5" :class="stat.color" />
                        </div>
                        <div>
                            <p :class="['text-2xl font-bold', textPrimary]">{{ stat.value }}</p>
                            <p :class="['text-xs', textMuted]">{{ stat.label }}</p>
                        </div>
                    </div>
                </GlassCard>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Phase Progress Chart -->
            <GlassCard title="Progression des Phases" animated class="lg:col-span-2">
                <div class="space-y-4">
                    <div v-for="phase in phaseBreakdown" :key="phase.phase" class="space-y-1">
                        <div class="flex justify-between items-center">
                            <span :class="['text-sm font-medium', textPrimary]">{{ phase.phase }}</span>
                            <span :class="['text-xs', textMuted]">
                                {{ phase.completed }}/{{ phase.total }} ({{ phase.completion_rate }}%)
                            </span>
                        </div>
                        <div class="h-3 rounded-full overflow-hidden" :class="isDarkText ? 'bg-gray-200' : 'bg-white/10'">
                            <div class="h-full flex">
                                <div 
                                    class="bg-green-500 transition-all duration-500"
                                    :style="{ width: `${(phase.completed / Math.max(phase.total, 1)) * 100}%` }"
                                ></div>
                                <div 
                                    class="bg-blue-500 transition-all duration-500"
                                    :style="{ width: `${(phase.in_progress / Math.max(phase.total, 1)) * 100}%` }"
                                ></div>
                                <div 
                                    class="bg-red-500 transition-all duration-500"
                                    :style="{ width: `${(phase.blocked / Math.max(phase.total, 1)) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 mt-4 text-xs" :class="textMuted">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded"></span> Completed</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded"></span> In Progress</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-500 rounded"></span> Blocked</span>
                </div>
            </GlassCard>

            <!-- RAG Distribution -->
            <GlassCard title="Distribution RAG" animated>
                <div class="space-y-3">
                    <div v-for="rag in ragDistribution" :key="rag.name" class="flex items-center gap-3">
                        <div 
                            class="w-4 h-4 rounded-full"
                            :style="{ backgroundColor: rag.color }"
                        ></div>
                        <span :class="['flex-1', textPrimary]">{{ rag.name }}</span>
                        <span :class="['text-xl font-bold', textPrimary]">{{ rag.value }}</span>
                        <span :class="['text-sm', textMuted]">
                            {{ getPercentage(rag.value) }}%
                        </span>
                    </div>
                </div>
                
                <!-- Mini donut -->
                <div class="mt-4 flex justify-center">
                    <svg class="w-24 h-24" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" fill="none" stroke-width="15" 
                            :stroke="isDarkText ? '#e5e7eb' : 'rgba(255,255,255,0.1)'" />
                        <circle cx="50" cy="50" r="40" fill="none" stroke-width="15"
                            stroke="#10b981" 
                            :stroke-dasharray="`${getArcLength('Green')} 251.2`"
                            stroke-dashoffset="0"
                            transform="rotate(-90 50 50)" />
                    </svg>
                </div>
            </GlassCard>
        </div>

        <!-- Overdue & Blocked Projects -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Overdue Projects -->
            <GlassCard animated>
                <template #header>
                    <div class="flex items-center gap-2">
                        <Clock class="w-5 h-5 text-red-500" />
                        <h3 :class="['font-semibold', textPrimary]">Projets en Retard ({{ overdueProjects?.length || 0 }})</h3>
                    </div>
                </template>
                
                <div v-if="overdueProjects?.length" class="space-y-3 max-h-80 overflow-y-auto">
                    <div 
                        v-for="project in overdueProjects" 
                        :key="project.id"
                        @click="goToProject(project.id)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer transition-all',
                            isDarkText ? 'bg-red-50 hover:bg-red-100 border border-red-200' : 'bg-red-500/10 hover:bg-red-500/20 border border-red-500/30'
                        ]"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p :class="['font-semibold text-sm', textPrimary]">{{ project.name }}</p>
                                <p :class="['text-xs', textMuted]">{{ project.code }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded">
                                -{{ project.days_overdue }}j
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span :class="['text-xs', textMuted]">Target: {{ project.target_date }}</span>
                            <ProgressBar :progress="project.completion_percent" class="w-24" />
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8">
                    <CheckCircle class="w-12 h-12 text-green-500 mx-auto mb-2" />
                    <p :class="textMuted">Aucun projet en retard 🎉</p>
                </div>
            </GlassCard>

            <!-- Blocked Projects -->
            <GlassCard animated>
                <template #header>
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="w-5 h-5 text-amber-500" />
                        <h3 :class="['font-semibold', textPrimary]">Projets Bloqués ({{ blockedProjects?.length || 0 }})</h3>
                    </div>
                </template>
                
                <div v-if="blockedProjects?.length" class="space-y-3 max-h-80 overflow-y-auto">
                    <div 
                        v-for="project in blockedProjects" 
                        :key="project.id"
                        @click="goToProject(project.id)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer transition-all',
                            isDarkText ? 'bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30'
                        ]"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p :class="['font-semibold text-sm', textPrimary]">{{ project.name }}</p>
                                <p :class="['text-xs', textMuted]">{{ project.code }} • {{ project.owner }}</p>
                            </div>
                            <span :class="['text-xs px-2 py-1 rounded', isDarkText ? 'bg-gray-200' : 'bg-white/20']">
                                {{ project.days_blocked }}j
                            </span>
                        </div>
                        <p :class="['text-xs p-2 rounded', isDarkText ? 'bg-amber-100 text-amber-800' : 'bg-amber-500/20 text-amber-200']">
                            🚫 {{ project.blockers }}
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-8">
                    <CheckCircle class="w-12 h-12 text-green-500 mx-auto mb-2" />
                    <p :class="textMuted">Aucun projet bloqué 🎉</p>
                </div>
            </GlassCard>
        </div>

        <!-- Changelog & Upcoming Deadlines -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Changelog -->
            <GlassCard animated>
                <template #header>
                    <div class="flex items-center gap-2">
                        <FileText class="w-5 h-5 text-prism-400" />
                        <h3 :class="['font-semibold', textPrimary]">Journal des Modifications</h3>
                    </div>
                </template>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div 
                        v-for="entry in changelog" 
                        :key="entry.id"
                        :class="[
                            'p-3 rounded-lg border-l-4',
                            getChangelogBorderClass(entry.action),
                            isDarkText ? 'bg-gray-50' : 'bg-white/5'
                        ]"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <p :class="['text-sm font-medium', textPrimary]">{{ entry.project_name }}</p>
                            <span :class="['text-xs', textMuted]">{{ entry.time }}</span>
                        </div>
                        <p :class="['text-sm', getChangelogTextClass(entry.action)]">
                            {{ entry.description }}
                        </p>
                        <p :class="['text-xs mt-1', textMuted]">par {{ entry.user }}</p>
                    </div>
                    
                    <div v-if="!changelog?.length" class="text-center py-8">
                        <FileText :class="['w-12 h-12 mx-auto mb-2', textMuted]" />
                        <p :class="textMuted">Aucune modification récente</p>
                    </div>
                </div>
            </GlassCard>

            <!-- Upcoming Deadlines -->
            <GlassCard animated>
                <template #header>
                    <div class="flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-blue-400" />
                        <h3 :class="['font-semibold', textPrimary]">Échéances à Venir</h3>
                    </div>
                </template>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div 
                        v-for="project in upcomingDeadlines" 
                        :key="project.id"
                        @click="goToProject(project.id)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer transition-all',
                            project.is_urgent 
                                ? (isDarkText ? 'bg-orange-50 border border-orange-200' : 'bg-orange-500/10 border border-orange-500/30')
                                : (isDarkText ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white/5 hover:bg-white/10')
                        ]"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p :class="['font-semibold text-sm', textPrimary]">{{ project.name }}</p>
                                <p :class="['text-xs', textMuted]">{{ project.code }}</p>
                            </div>
                            <div class="text-right">
                                <p :class="[
                                    'text-sm font-bold',
                                    project.days_remaining <= 3 ? 'text-red-500' : 
                                    project.days_remaining <= 7 ? 'text-orange-500' : 'text-blue-500'
                                ]">
                                    {{ project.days_remaining }}j
                                </p>
                                <p :class="['text-xs', textMuted]">{{ project.target_date }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <ProgressBar :progress="project.completion_percent" class="flex-1" />
                            <StatusBadge :status="project.rag_status" size="sm" />
                        </div>
                    </div>
                    
                    <div v-if="!upcomingDeadlines?.length" class="text-center py-8">
                        <Calendar :class="['w-12 h-12 mx-auto mb-2', textMuted]" />
                        <p :class="textMuted">Pas d'échéances imminentes</p>
                    </div>
                </div>
            </GlassCard>
        </div>

        <!-- Velocity Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <GlassCard animated>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-green-500/20">
                        <Rocket class="w-8 h-8 text-green-500" />
                    </div>
                    <div>
                        <p :class="['text-3xl font-bold', textPrimary]">{{ healthMetrics?.velocity || 0 }}</p>
                        <p :class="['text-sm', textMuted]">Projets déployés (30j)</p>
                    </div>
                </div>
            </GlassCard>
            
            <GlassCard animated>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-blue-500/20">
                        <PlayCircle class="w-8 h-8 text-blue-500" />
                    </div>
                    <div>
                        <p :class="['text-3xl font-bold', textPrimary]">{{ healthMetrics?.started_last_30 || 0 }}</p>
                        <p :class="['text-sm', textMuted]">Nouveaux projets (30j)</p>
                    </div>
                </div>
            </GlassCard>
            
            <GlassCard animated>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-purple-500/20">
                        <TrendingUp class="w-8 h-8 text-purple-500" />
                    </div>
                    <div>
                        <p :class="['text-3xl font-bold', textPrimary]">{{ healthMetrics?.avg_completion || 0 }}%</p>
                        <p :class="['text-sm', textMuted]">Completion moyenne</p>
                    </div>
                </div>
            </GlassCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from '@/Composables/useRoute';
import { 
    FolderKanban, AlertTriangle, CheckCircle, Clock, TrendingUp,
    FileText, Calendar, Rocket, PlayCircle, Flame, AlertCircle
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import StatusBadge from '@/Components/Glass/StatusBadge.vue';
import ProgressBar from '@/Components/Glass/ProgressBar.vue';
import { useTheme } from '@/Composables/useTheme';

const { isDarkText } = useTheme();

const textPrimary = computed(() => isDarkText.value ? 'text-gray-900' : 'text-white');
const textMuted = computed(() => isDarkText.value ? 'text-gray-500' : 'text-slate-400');

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    healthMetrics: { type: Object, default: () => ({}) },
    ragDistribution: { type: Array, default: () => [] },
    phaseBreakdown: { type: Array, default: () => [] },
    overdueProjects: { type: Array, default: () => [] },
    blockedProjects: { type: Array, default: () => [] },
    upcomingDeadlines: { type: Array, default: () => [] },
    changelog: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    criticalProjects: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
});

// Quick stats
const quickStats = computed(() => [
    { 
        label: 'Total Projets', 
        value: props.healthMetrics?.total || 0, 
        icon: FolderKanban, 
        bg: 'bg-prism-500/20', 
        color: 'text-prism-400' 
    },
    { 
        label: 'En Cours', 
        value: props.healthMetrics?.in_progress || 0, 
        icon: PlayCircle, 
        bg: 'bg-blue-500/20', 
        color: 'text-blue-400' 
    },
    { 
        label: 'Déployés', 
        value: props.healthMetrics?.deployed || 0, 
        icon: CheckCircle, 
        bg: 'bg-green-500/20', 
        color: 'text-green-400' 
    },
    { 
        label: 'À Risque', 
        value: props.healthMetrics?.at_risk || 0, 
        icon: AlertTriangle, 
        bg: 'bg-red-500/20', 
        color: 'text-red-400' 
    },
]);

// Helpers
const getHealthColor = (score) => {
    if (score >= 80) return '#10b981';
    if (score >= 60) return '#f59e0b';
    return '#ef4444';
};

const getHealthLabel = (score) => {
    if (score >= 80) return 'Excellent';
    if (score >= 60) return 'Correct';
    if (score >= 40) return 'À surveiller';
    return 'Critique';
};

const getPercentage = (value) => {
    const total = props.ragDistribution?.reduce((sum, r) => sum + r.value, 0) || 1;
    return Math.round((value / total) * 100);
};

const getArcLength = (name) => {
    const item = props.ragDistribution?.find(r => r.name === name);
    const total = props.ragDistribution?.reduce((sum, r) => sum + r.value, 0) || 1;
    return ((item?.value || 0) / total) * 251.2;
};

const getAlertClass = (type) => ({
    'danger': 'bg-red-500/20 text-red-500 border border-red-500/30',
    'warning': 'bg-amber-500/20 text-amber-500 border border-amber-500/30',
    'info': 'bg-blue-500/20 text-blue-500 border border-blue-500/30',
}[type] || 'bg-gray-500/20 text-gray-500');

const getAlertIcon = (icon) => ({
    'clock': Clock,
    'alert-triangle': AlertTriangle,
    'flame': Flame,
    'calendar': Calendar,
    'file-edit': FileText,
}[icon] || AlertCircle);

const getChangelogBorderClass = (action) => ({
    'phase_updated': 'border-l-blue-500',
    'created': 'border-l-green-500',
    'updated': 'border-l-amber-500',
    'status_changed': 'border-l-purple-500',
}[action] || 'border-l-gray-500');

const getChangelogTextClass = (action) => ({
    'phase_updated': isDarkText.value ? 'text-blue-700' : 'text-blue-300',
    'created': isDarkText.value ? 'text-green-700' : 'text-green-300',
    'updated': isDarkText.value ? 'text-amber-700' : 'text-amber-300',
}[action] || textMuted.value);

const goToProject = (id) => {
    router.visit(route('projects.show', id));
};
</script>
