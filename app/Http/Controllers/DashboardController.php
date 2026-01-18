<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Dashboard index - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        $data = [
            'stats' => $this->dashboardService->getKpis(),
            'ragDistribution' => $this->dashboardService->getRagDistribution(),
            'categoryDistribution' => $this->dashboardService->getCategoryDistribution(),
            'devStatusDistribution' => $this->dashboardService->getDevStatusDistribution(),
            'criticalProjects' => $this->dashboardService->getCriticalProjects(),
            'upcomingDeadlines' => $this->dashboardService->getUpcomingDeadlines(),
            'recentActivities' => $this->dashboardService->getRecentActivity(),
            // New PMP features
            'healthMetrics' => $this->dashboardService->getHealthMetrics(),
            'overdueProjects' => $this->dashboardService->getOverdueProjects(),
            'blockedProjects' => $this->dashboardService->getBlockedProjects(),
            'phaseBreakdown' => $this->dashboardService->getPhaseBreakdown(),
            'changelog' => $this->dashboardService->getChangelog(),
            'alerts' => $this->dashboardService->getAlerts(),
            'allProjects' => \App\Models\Project::with('category')
                ->select('id', 'name', 'project_code', 'dev_status', 'rag_status', 'completion_percent', 'submission_date', 'target_date', 'category_id')
                ->get(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Get stats summary (API)
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_projects' => \App\Models\Project::count(),
            'green_projects' => \App\Models\Project::where('rag_status', 'Green')->count(),
            'amber_projects' => \App\Models\Project::where('rag_status', 'Amber')->count(),
            'red_projects' => \App\Models\Project::where('rag_status', 'Red')->count(),
            'by_category' => \App\Models\Project::select('categories.name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->join('categories', 'projects.category_id', '=', 'categories.id')
                ->groupBy('categories.name')
                ->get()
                ->map(fn($item) => ['name' => $item->name, 'count' => $item->count])
                ->toArray(),
        ];

        return response()->json($stats);
    }

    /**
     * Get critical projects (API)
     */
    public function criticalProjects(): JsonResponse
    {
        return response()->json($this->dashboardService->getCriticalProjects());
    }

    /**
     * Get recent activities (API)
     */
    public function recentActivities(): JsonResponse
    {
        return response()->json($this->dashboardService->getRecentActivity());
    }

    /**
     * Get KPIs (API)
     */
    public function kpis(): JsonResponse
    {
        return response()->json($this->dashboardService->getKpis());
    }

    /**
     * Get RAG distribution (API)
     */
    public function ragDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getRagDistribution());
    }

    /**
     * Get category distribution (API)
     */
    public function categoryDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getCategoryDistribution());
    }

    /**
     * Get dev status distribution (API)
     */
    public function devStatusDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getDevStatusDistribution());
    }

    /**
     * Get deployment timeline (API)
     */
    public function deploymentTimeline(): JsonResponse
    {
        return response()->json($this->dashboardService->getDeploymentTimeline());
    }

    /**
     * Get upcoming deadlines (API)
     */
    public function upcomingDeadlines(): JsonResponse
    {
        return response()->json($this->dashboardService->getUpcomingDeadlines());
    }

    /**
     * Get risk matrix (API)
     */
    public function riskMatrix(): JsonResponse
    {
        return response()->json($this->dashboardService->getRiskMatrix());
    }

    /**
     * Refresh cache (API)
     */
    public function refreshCache(): JsonResponse
    {
        $this->dashboardService->clearCache();

        return response()->json([
            'message' => 'Cache rafraîchi avec succès.',
        ]);
    }
}
