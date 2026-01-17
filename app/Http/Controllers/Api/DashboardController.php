<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function kpis(): JsonResponse
    {
        return response()->json($this->dashboardService->getKpis());
    }

    public function ragDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getRagDistribution());
    }

    public function categoryDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getCategoryDistribution());
    }

    public function devStatusDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getDevStatusDistribution());
    }

    public function deploymentTimeline(): JsonResponse
    {
        return response()->json($this->dashboardService->getDeploymentTimeline());
    }

    public function recentActivity(): JsonResponse
    {
        return response()->json($this->dashboardService->getRecentActivity());
    }

    public function criticalProjects(): JsonResponse
    {
        return response()->json($this->dashboardService->getCriticalProjects());
    }

    public function upcomingDeadlines(): JsonResponse
    {
        return response()->json($this->dashboardService->getUpcomingDeadlines());
    }

    public function riskMatrix(): JsonResponse
    {
        return response()->json($this->dashboardService->getRiskMatrix());
    }

    public function refreshCache(): JsonResponse
    {
        $this->dashboardService->clearCache();

        return response()->json([
            'message' => 'Cache rafraîchi avec succès.',
        ]);
    }
}
