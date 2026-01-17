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
                'days_remaining' => now()->startOfDay()->diffInDays($p->target_date->startOfDay()),
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
}
