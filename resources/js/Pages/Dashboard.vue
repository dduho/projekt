<template>
    <AppLayout 
        page-title="Dashboard"
        page-description="Vue d'ensemble de vos projets"
    >
        <!-- KPIs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <GlassCard 
                v-for="kpi in kpis" 
                :key="kpi.label"
                animated
                class="delay-100"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-400 mb-1">{{ kpi.label }}</p>
                        <h3 class="text-3xl font-bold text-white">{{ kpi.value }}</h3>
                        <p class="text-xs text-gray-400 mt-1">{{ kpi.subtitle }}</p>
                    </div>
                    <div 
                        class="w-12 h-12 rounded-xl flex items-center justify-center"
                        :class="kpi.bgClass"
                    >
                        <component :is="kpi.icon" class="w-6 h-6" :class="kpi.iconClass" />
                    </div>
                </div>
                
                <!-- Trend -->
                <div v-if="kpi.trend" class="mt-4 flex items-center gap-2">
                    <component 
                        :is="kpi.trend.value > 0 ? TrendingUp : TrendingDown" 
                        class="w-4 h-4"
                        :class="kpi.trend.value > 0 ? 'text-green-400' : 'text-red-400'"
                    />
                    <span 
                        class="text-sm font-medium"
                        :class="kpi.trend.value > 0 ? 'text-green-400' : 'text-red-400'"
                    >
                        {{ Math.abs(kpi.trend.value) }}%
                    </span>
                    <span class="text-xs text-gray-400">{{ kpi.trend.label }}</span>
                </div>
            </GlassCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- RAG Status Distribution -->
            <GlassCard 
                title="Statut RAG"
                animated
                class="lg:col-span-2 delay-200"
            >
                <apexchart
                    v-if="ragChartOptions"
                    type="donut"
                    height="300"
                    :options="ragChartOptions"
                    :series="ragChartSeries"
                />
            </GlassCard>

            <!-- Critical Projects -->
            <GlassCard title="Projets Critiques" animated class="delay-300">
                <div class="space-y-3">
                    <div 
                        v-for="project in criticalProjects" 
                        :key="project.id"
                        class="p-3 glass-subtle rounded-lg hover:glass-hover cursor-pointer transition-all"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="text-sm font-semibold text-white">{{ project.name }}</h4>
                            <StatusBadge :status="project.rag_status" />
                        </div>
                        <p class="text-xs text-gray-400 mb-2">{{ project.project_code }}</p>
                        <ProgressBar 
                            :value="project.overall_progress"
                            :max="100"
                            label="Progression"
                        />
                    </div>
                    
                    <div v-if="criticalProjects.length === 0" class="text-center py-8">
                        <CheckCircle class="w-12 h-12 text-green-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-400">Aucun projet critique</p>
                    </div>
                </div>
            </GlassCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Projects by Category -->
            <GlassCard title="Projets par Catégorie" animated class="delay-100">
                <apexchart
                    v-if="categoryChartOptions"
                    type="bar"
                    height="300"
                    :options="categoryChartOptions"
                    :series="categoryChartSeries"
                />
            </GlassCard>

            <!-- Recent Activity -->
            <GlassCard title="Activité Récente" animated class="delay-200">
                <div class="space-y-3 max-h-[300px] overflow-y-auto">
                    <div 
                        v-for="activity in recentActivities" 
                        :key="activity.id"
                        class="flex items-start gap-3 p-3 glass-subtle rounded-lg"
                    >
                        <div 
                            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                            :class="getActivityIconBg(activity.type)"
                        >
                            <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white">{{ activity.description }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ formatDate(activity.created_at) }}</p>
                        </div>
                    </div>
                    
                    <div v-if="recentActivities.length === 0" class="text-center py-8">
                        <Activity class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-400">Aucune activité récente</p>
                    </div>
                </div>
            </GlassCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { 
    FolderKanban, 
    AlertTriangle, 
    CheckCircle, 
    Clock,
    TrendingUp,
    TrendingDown,
    Activity,
    FileEdit,
    MessageSquare
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import StatusBadge from '@/Components/Glass/StatusBadge.vue';
import ProgressBar from '@/Components/Glass/ProgressBar.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true
    },
    criticalProjects: {
        type: Array,
        default: () => []
    },
    recentActivities: {
        type: Array,
        default: () => []
    }
});

// KPIs
const kpis = computed(() => [
    {
        label: 'Total Projets',
        value: props.stats.total_projects,
        subtitle: 'Projets actifs',
        icon: FolderKanban,
        bgClass: 'bg-prism-500/20',
        iconClass: 'text-prism-400',
        trend: { value: 12, label: 'vs mois dernier' }
    },
    {
        label: 'Projets GREEN',
        value: props.stats.green_projects,
        subtitle: 'En bonne voie',
        icon: CheckCircle,
        bgClass: 'bg-green-500/20',
        iconClass: 'text-green-400',
    },
    {
        label: 'Projets AMBER',
        value: props.stats.amber_projects,
        subtitle: 'À surveiller',
        icon: Clock,
        bgClass: 'bg-amber-500/20',
        iconClass: 'text-amber-400',
    },
    {
        label: 'Projets RED',
        value: props.stats.red_projects,
        subtitle: 'Critiques',
        icon: AlertTriangle,
        bgClass: 'bg-red-500/20',
        iconClass: 'text-red-400',
    }
]);

// RAG Chart
const ragChartSeries = computed(() => [
    props.stats.green_projects,
    props.stats.amber_projects,
    props.stats.red_projects,
]);

const ragChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        background: 'transparent',
        foreColor: '#9ca3af'
    },
    labels: ['GREEN', 'AMBER', 'RED'],
    colors: ['#10b981', '#f59e0b', '#ef4444'],
    legend: {
        position: 'bottom',
        labels: {
            colors: '#9ca3af'
        }
    },
    dataLabels: {
        enabled: true,
        style: {
            colors: ['#fff']
        }
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        color: '#fff'
                    }
                }
            }
        }
    },
    theme: {
        mode: 'dark'
    }
}));

// Category Chart
const categoryChartSeries = computed(() => [{
    name: 'Projets',
    data: props.stats.by_category?.map(c => c.count) || []
}]);

const categoryChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        background: 'transparent',
        foreColor: '#9ca3af',
        toolbar: {
            show: false
        }
    },
    xaxis: {
        categories: props.stats.by_category?.map(c => c.name) || [],
        labels: {
            style: {
                colors: '#9ca3af'
            }
        }
    },
    yaxis: {
        labels: {
            style: {
                colors: '#9ca3af'
            }
        }
    },
    plotOptions: {
        bar: {
            borderRadius: 8,
            distributed: true
        }
    },
    colors: ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444'],
    dataLabels: {
        enabled: false
    },
    legend: {
        show: false
    },
    grid: {
        borderColor: 'rgba(255, 255, 255, 0.1)'
    },
    theme: {
        mode: 'dark'
    }
}));

// Activity helpers
const getActivityIcon = (type) => {
    const icons = {
        'project_created': FolderKanban,
        'project_updated': FileEdit,
        'comment_added': MessageSquare,
        'phase_updated': Activity,
        'risk_created': AlertTriangle,
    };
    return icons[type] || Activity;
};

const getActivityIconBg = (type) => {
    const colors = {
        'project_created': 'bg-prism-500/20 text-prism-400',
        'project_updated': 'bg-amber-500/20 text-amber-400',
        'comment_added': 'bg-green-500/20 text-green-400',
        'phase_updated': 'bg-blue-500/20 text-blue-400',
        'risk_created': 'bg-red-500/20 text-red-400',
    };
    return colors[type] || 'bg-gray-500/20 text-gray-400';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
