<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}

    /**
     * Export projects to Excel/CSV
     */
    public function exportProjects(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'xlsx');
        $filters = $request->only(['category_id', 'rag_status', 'dev_status', 'owner_id']);

        return $this->exportService->exportProjects($filters, $format);
    }

    /**
     * Export risks to Excel/CSV
     */
    public function exportRisks(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'xlsx');
        $filters = $request->only(['project_id', 'risk_score', 'status']);

        return $this->exportService->exportRisks($filters, $format);
    }

    /**
     * Export change requests to Excel/CSV
     */
    public function exportChangeRequests(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'xlsx');
        $filters = $request->only(['project_id', 'status']);

        return $this->exportService->exportChangeRequests($filters, $format);
    }

    /**
     * Export dashboard to PDF
     */
    public function exportDashboard(): StreamedResponse
    {
        return $this->exportService->exportDashboardPdf();
    }
}
