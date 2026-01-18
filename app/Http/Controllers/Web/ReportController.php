<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Throwable;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use AuthorizesRequests;
    public function __construct(protected ReportService $reportService)
    {
    }

    /**
     * Page liste des rapports
     */
    public function index()
    {
        $projects = Project::select('id', 'name', 'project_code', 'dev_status', 'rag_status', 'completion_percent')
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Données JSON pour tableau données
     */
    public function projectData(Project $project)
    {
        $this->authorize('view', $project);

        $reportData = $this->reportService->generateProjectReport($project);

        return response()->json($reportData);
    }

    /**
     * PDF rapport projet
     */
    public function projectPdf(Project $project)
    {
        $this->authorize('view', $project);

        $reportData = $this->reportService->generateProjectReport($project);

        $pdf = Pdf::loadView('reports.project', $reportData);

        return $pdf->download("rapport_projet_{$project->project_code}.pdf");
    }

    /**
     * Excel rapport projet
     */
    public function projectExcel(Project $project)
    {
        $this->authorize('view', $project);

        $reportData = $this->reportService->generateProjectReport($project);
        try {
            return Excel::download(
                new \App\Exports\ProjectReportExport($reportData),
                "rapport_projet_{$project->project_code}.xlsx"
            );
        } catch (Throwable $e) {
            Log::error('Project Excel export failed', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Excel export failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Données tendances
     */
    public function projectTrends(Project $project)
    {
        $this->authorize('view', $project);

        $trends = $this->reportService->getTrendData($project->id);
        $forecast = $this->reportService->getForecast($project);

        return response()->json([
            'trends' => $trends,
            'forecast' => $forecast,
        ]);
    }

    /**
     * PDF rapport portfolio
     */
    public function portfolioPdf()
    {
        $this->authorize('viewAny', Project::class);

        $reportData = $this->reportService->generatePortfolioReport();

        $pdf = Pdf::loadView('reports.portfolio', $reportData);

        return $pdf->download("rapport_portfolio_" . now()->format('Y-m-d') . ".pdf");
    }

    /**
     * Excel rapport portfolio
     */
    public function portfolioExcel()
    {
        $this->authorize('viewAny', Project::class);

        $reportData = $this->reportService->generatePortfolioReport();
        try {
            return Excel::download(
                new \App\Exports\PortfolioReportExport($reportData),
                "rapport_portfolio_" . now()->format('Y-m-d') . ".xlsx"
            );
        } catch (Throwable $e) {
            Log::error('Portfolio Excel export failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Excel export failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Données JSON portfolio
     */
    public function portfolioData()
    {
        $this->authorize('viewAny', Project::class);

        $reportData = $this->reportService->generatePortfolioReport();

        return response()->json($reportData);
    }
}
