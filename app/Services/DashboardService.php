<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function getKpis(): array
    {
        return Cache::remember('dashboard.kpis', self::CACHE_TTL, function () {
            $totalProjects = Project::count();

            return [
                'total_projects' => $totalProjects,
                'deployed' => $this->getStatusKpi('Deployed', $totalProjects),
                'in_progress' => $this->getStatusKpi('In Development', $totalProjects),
                'testing' => $this->getStatusKpi('Testing', $totalProjects),
                'uat' => $this->getStatusKpi('UAT', $totalProjects),
                'on_hold' => $this->getStatusKpi('On Hold', $totalProjects),
                'not_started' => $this->getStatusKpi('Not Started', $totalProjects),
                'frs_signoff' => [
                    'count' => Project::withFrsSignoff()->count(),
                    'percent' => $totalProjects > 0
                        ? round(Project::withFrsSignoff()->count() / $totalProjects * 100)
                        : 0,
                ],
                'critical_risks' => Risk::critical()->open()->count(),
                'open_risks' => Risk::active()->count(),
                'pending_changes' => ChangeRequest::pending()->count(),
                'blocked_projects' => Project::whereNotNull('blockers')->count(),
                'rag_summary' => [
                    'green' => Project::byRagStatus('Green')->count(),
                    'amber' => Project::byRagStatus('Amber')->count(),
                    'red' => Project::byRagStatus('Red')->count(),
                ],
            ];
        });
    }

    private function getStatusKpi(string $status, int $total): array
    {
        $count = Project::byDevStatus($status)->count();
        return [
            'count' => $count,
            'percent' => $total > 0 ? round($count / $total * 100) : 0,
        ];
    }

    public function getRagDistribution(): array
    {
        return Cache::remember('dashboard.rag', self::CACHE_TTL, function () {
            $distribution = Project::select('rag_status', DB::raw('COUNT(*) as count'))
                ->groupBy('rag_status')
                ->pluck('count', 'rag_status')
                ->toArray();

            return [
                ['name' => 'Green', 'value' => $distribution['Green'] ?? 0, 'color' => '#10B981'],
                ['name' => 'Amber', 'value' => $distribution['Amber'] ?? 0, 'color' => '#F59E0B'],
                ['name' => 'Red', 'value' => $distribution['Red'] ?? 0, 'color' => '#EF4444'],
            ];
        });
    }

    public function getCategoryDistribution(): array
    {
        return Cache::remember('dashboard.categories', self::CACHE_TTL, function () {
            return Project::select('categories.name', 'categories.color', DB::raw('COUNT(*) as count'))
                ->join('categories', 'projects.category_id', '=', 'categories.id')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderByDesc('count')
                ->get()
                ->map(fn($item) => [
                    'name' => $item->name,
                    'count' => $item->count,
                    'color' => $item->color,
                ])
                ->toArray();
        });
    }

    public function getDevStatusDistribution(): array
    {
        return Cache::remember('dashboard.devstatus', self::CACHE_TTL, function () {
            $statuses = ['Not Started', 'In Development', 'Testing', 'UAT', 'Deployed', 'On Hold'];
            $colors = ['#94a3b8', '#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'];

            return collect($statuses)->map(function ($status, $index) use ($colors) {
                return [
                    'name' => $status,
                    'count' => Project::byDevStatus($status)->count(),
                    'color' => $colors[$index],
                ];
            })->toArray();
        });
    }

    public function getDeploymentTimeline(): array
    {
        return Cache::remember('dashboard.timeline', self::CACHE_TTL, function () {
            $results = Project::where('dev_status', 'Deployed')
                ->whereNotNull('go_live_date')
                ->select(
                    DB::raw("DATE_FORMAT(go_live_date, '%Y-%m') as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->limit(12)
                ->pluck('count', 'month')
                ->toArray();

            $timeline = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i)->format('Y-m');
                $timeline[] = [
                    'month' => now()->subMonths($i)->format('M Y'),
                    'count' => $results[$month] ?? 0,
                ];
            }

            return $timeline;
        });
    }

    public function getRecentActivity(int $limit = 15): array
    {
        return ActivityLog::with(['user', 'loggable'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'user' => $activity->user?->name ?? 'Système',
                'user_avatar' => $activity->user?->avatar_url,
                'user_initials' => $activity->user?->initials ?? 'SY',
                'action' => $activity->action,
                'action_formatted' => $activity->formatted_action,
                'type' => $activity->subject_type,
                'subject' => $activity->subject_name,
                'subject_id' => $activity->loggable_id,
                'changes' => $activity->changes,
                'created_at' => $activity->created_at->diffForHumans(),
                'created_at_full' => $activity->created_at->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    public function getCriticalProjects(int $limit = 10): array
    {
        return Project::with(['category', 'owner'])
            ->withCount(['risks' => fn($q) => $q->critical()->open()])
            ->where(function ($query) {
                $query->where('rag_status', 'Red')
                    ->orWhereHas('risks', fn($q) => $q->critical()->open());
            })
            ->orderByDesc('risks_count')
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'rag_status' => $p->rag_status,
                'dev_status' => $p->dev_status,
                'category' => $p->category->name,
                'category_color' => $p->category->color,
                'owner' => $p->owner?->name ?? 'Non assigné',
                'owner_avatar' => $p->owner?->avatar_url,
                'critical_risks' => $p->risks_count,
                'blockers' => $p->blockers,
                'completion_percent' => $p->completion_percent,
                'target_date' => $p->target_date?->format('d/m/Y'),
            ])
            ->toArray();
    }

    public function getUpcomingDeadlines(int $days = 30): array
    {
        return Project::with(['category', 'owner'])
            ->whereNotNull('target_date')
            ->whereBetween('target_date', [now(), now()->addDays($days)])
            ->whereNotIn('dev_status', ['Deployed', 'On Hold'])
            ->orderBy('target_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'target_date' => $p->target_date->format('d/m/Y'),
                'days_remaining' => abs(now()->diffInDays($p->target_date)),
                'rag_status' => $p->rag_status,
                'dev_status' => $p->dev_status,
                'completion_percent' => $p->completion_percent,
                'owner' => $p->owner?->name ?? 'Non assigné',
                'is_urgent' => now()->diffInDays($p->target_date) <= 7,
            ])
            ->toArray();
    }

    public function getRiskMatrix(): array
    {
        $matrix = [];
        $impacts = ['Low', 'Medium', 'High', 'Critical'];
        $probabilities = ['Low', 'Medium', 'High'];

        foreach ($impacts as $impact) {
            foreach ($probabilities as $probability) {
                $count = Risk::where('impact', $impact)
                    ->where('probability', $probability)
                    ->active()
                    ->count();

                $matrix[] = [
                    'impact' => $impact,
                    'probability' => $probability,
                    'count' => $count,
                ];
            }
        }

        return $matrix;
    }

    public function clearCache(): void
    {
        Cache::forget('dashboard.kpis');
        Cache::forget('dashboard.rag');
        Cache::forget('dashboard.categories');
        Cache::forget('dashboard.devstatus');
        Cache::forget('dashboard.timeline');
    }

    /**
     * Get overdue projects (past target date but not deployed)
     */
    public function getOverdueProjects(): array
    {
        return Project::with(['category', 'owner'])
            ->whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->whereNotIn('dev_status', ['Deployed'])
            ->orderBy('target_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'target_date' => $p->target_date->format('d/m/Y'),
                'days_overdue' => abs($p->target_date->diffInDays(now())),
                'rag_status' => $p->calculated_rag_status ?? $p->rag_status,
                'dev_status' => $p->dev_status,
                'completion_percent' => $p->calculated_completion_percent ?? $p->completion_percent,
                'owner' => $p->owner?->name ?? 'Non assigné',
                'category' => $p->category?->name ?? '-',
                'category_color' => $p->category?->color ?? '#6366f1',
            ])
            ->toArray();
    }

    /**
     * Get blocked projects
     */
    public function getBlockedProjects(): array
    {
        return Project::with(['category', 'owner'])
            ->whereNotNull('blockers')
            ->where('blockers', '!=', '')
            ->whereNotIn('dev_status', ['Deployed'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'blockers' => $p->blockers,
                'dev_status' => $p->dev_status,
                'target_date' => $p->target_date?->format('d/m/Y'),
                'days_until_deadline' => $p->target_date ? (int) ($p->target_date->diffInDays(now(), false) * -1) : null,
                'owner' => $p->owner?->name ?? 'Non assigné',
                'category' => $p->category?->name ?? '-',
            ])
            ->toArray();
    }

    /**
     * Get project health summary with velocity metrics
     */
    public function getHealthMetrics(): array
    {
        $total = Project::count();
        $deployed = Project::where('dev_status', 'Deployed')->count();
        $inProgress = Project::whereIn('dev_status', ['In Development', 'Testing', 'UAT'])->count();
        $overdue = Project::whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->whereNotIn('dev_status', ['Deployed'])
            ->count();
        $blocked = Project::whereNotNull('blockers')->whereNotIn('dev_status', ['Deployed'])->count();
        $atRisk = Project::where('rag_status', 'Red')->count();

        // Calculate average completion
        $avgCompletion = Project::whereNotIn('dev_status', ['Deployed', 'Not Started'])
            ->avg('completion_percent') ?? 0;

        // Velocity: projects completed in last 30 days
        $deployedLast30Days = Project::where('dev_status', 'Deployed')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        // Projects started in last 30 days
        $startedLast30Days = Project::where('created_at', '>=', now()->subDays(30))->count();

        return [
            'total' => $total,
            'deployed' => $deployed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'blocked' => $blocked,
            'at_risk' => $atRisk,
            'avg_completion' => round($avgCompletion),
            'velocity' => $deployedLast30Days,
            'started_last_30' => $startedLast30Days,
            'health_score' => $this->calculateHealthScore($total, $overdue, $blocked, $atRisk),
        ];
    }

    private function calculateHealthScore(int $total, int $overdue, int $blocked, int $atRisk): int
    {
        if ($total === 0) return 100;
        
        // Deduct points for issues
        $score = 100;
        $score -= ($overdue / $total) * 40; // Up to -40 for overdue
        $score -= ($blocked / $total) * 30; // Up to -30 for blocked
        $score -= ($atRisk / $total) * 30;  // Up to -30 for at risk
        
        return max(0, min(100, round($score)));
    }

    /**
     * Get phase completion breakdown
     */
    public function getPhaseBreakdown(): array
    {
        $phases = ['FRS', 'Development', 'Testing', 'UAT', 'Deployment'];
        $breakdown = [];

        foreach ($phases as $phase) {
            $total = \App\Models\ProjectPhase::where('phase', $phase)->count();
            $completed = \App\Models\ProjectPhase::where('phase', $phase)
                ->where('status', 'Completed')->count();
            $inProgress = \App\Models\ProjectPhase::where('phase', $phase)
                ->where('status', 'In Progress')->count();
            $blocked = \App\Models\ProjectPhase::where('phase', $phase)
                ->where('status', 'Blocked')->count();

            $breakdown[] = [
                'phase' => $phase,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'blocked' => $blocked,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        }

        return $breakdown;
    }

    /**
     * Get recent changelog (phase updates, status changes)
     */
    public function getChangelog(int $limit = 20): array
    {
        return ActivityLog::with(['user', 'loggable'])
            ->whereIn('action', ['phase_updated', 'created', 'updated', 'status_changed'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'user' => $activity->user?->name ?? 'Système',
                'action' => $activity->action,
                'description' => $this->formatChangelogDescription($activity),
                'project_id' => $activity->loggable_id,
                'project_name' => $activity->loggable?->name ?? 'Projet supprimé',
                'changes' => $activity->changes,
                'time' => $activity->created_at->diffForHumans(),
                'date' => $activity->created_at->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    private function formatChangelogDescription($activity): string
    {
        $changes = $activity->changes ?? [];
        
        switch ($activity->action) {
            case 'phase_updated':
                $phase = $changes['phase'] ?? 'Phase';
                $old = $changes['old_status'] ?? '?';
                $new = $changes['new_status'] ?? '?';
                return "{$phase}: {$old} → {$new}";
            
            case 'created':
                return "Projet créé: " . ($changes['name'] ?? 'Nouveau projet');
            
            case 'updated':
            case 'status_changed':
                if (isset($changes['dev_status'])) {
                    return "Statut: " . ($changes['old'] ?? '?') . " → " . ($changes['dev_status'] ?? '?');
                }
                return "Projet mis à jour";
            
            default:
                return $activity->action;
        }
    }

    /**
     * Get alerts for dashboard
     */
    public function getAlerts(): array
    {
        $alerts = [];

        // Overdue projects alert
        $overdueCount = Project::whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->whereNotIn('dev_status', ['Deployed'])
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'clock',
                'title_key' => 'alerts.overdue_projects',
                'message_key' => 'alerts.overdue_projects_message',
                'count' => $overdueCount,
                'action' => 'overdue',
            ];
        }

        // Blocked projects alert
        $blockedCount = Project::whereNotNull('blockers')
            ->whereNotIn('dev_status', ['Deployed'])
            ->count();

        if ($blockedCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'alert-triangle',
                'title_key' => 'alerts.blocked_projects',
                'message_key' => 'alerts.blocked_projects_message',
                'count' => $blockedCount,
                'action' => 'blocked',
            ];
        }

        // Critical risks alert
        $criticalRisks = Risk::critical()->open()->count();

        if ($criticalRisks > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'flame',
                'title_key' => 'alerts.critical_risks',
                'message_key' => 'alerts.critical_risks_message',
                'count' => $criticalRisks,
                'action' => 'risks',
            ];
        }

        // Upcoming deadlines (next 7 days)
        $urgentDeadlines = Project::whereNotNull('target_date')
            ->whereBetween('target_date', [now(), now()->addDays(7)])
            ->whereNotIn('dev_status', ['Deployed'])
            ->count();

        if ($urgentDeadlines > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'title_key' => 'alerts.upcoming_deadlines',
                'message_key' => 'alerts.upcoming_deadlines_message',
                'count' => $urgentDeadlines,
                'action' => 'deadlines',
            ];
        }

        // Pending change requests
        $pendingChanges = ChangeRequest::pending()->count();

        if ($pendingChanges > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'file-edit',
                'title_key' => 'alerts.pending_changes',
                'message_key' => 'alerts.pending_changes_message',
                'count' => $pendingChanges,
                'action' => 'changes',
            ];
        }

        return $alerts;
    }
}
