<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->dashboardService->getKpis(),
            'ragDistribution' => $this->dashboardService->getRagDistribution(),
            'categoryDistribution' => $this->dashboardService->getCategoryDistribution(),
            'devStatusDistribution' => $this->dashboardService->getDevStatusDistribution(),
            'criticalProjects' => $this->dashboardService->getCriticalProjects(),
            'upcomingDeadlines' => $this->dashboardService->getUpcomingDeadlines(),
            'recentActivities' => $this->dashboardService->getRecentActivity(),
        ]);
    }
}
