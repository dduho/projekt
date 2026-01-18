<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Risk;
use App\Models\ChangeRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function exportProjects(array $filters = [], string $format = 'xlsx'): StreamedResponse
    {
        $query = Project::with(['category', 'phases'])
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($filters['rag_status'] ?? null, fn($q, $v) => $q->where('rag_status', $v))
            ->when($filters['dev_status'] ?? null, fn($q, $v) => $q->where('dev_status', $v))
            ->when($filters['owner'] ?? null, fn($q, $v) => $q->where('owner', 'LIKE', "%{$v}%"));

        $projects = $query->get();

        $data = $projects->map(function ($project) {
            return [
                'Code' => $project->project_code,
                'Nom' => $project->name,
                'Categorie' => $project->category?->name,
                'Proprietaire' => $project->owner,
                'Priorite' => $project->priority,
                'Statut RAG' => $project->rag_status,
                'Statut Dev' => $project->dev_status,
                'Statut FRS' => $project->frs_status,
                'Avancement' => $project->completion_percent . '%',
                'Date soumission' => $project->submission_date?->format('d/m/Y'),
                'Date cible' => $project->target_date?->format('d/m/Y'),
                'Date go live' => $project->go_live_date?->format('d/m/Y'),
                'Bloqueurs' => $project->blockers,
            ];
        });

        return $this->generateCsv($data, 'projects_export_' . now()->format('Y-m-d'));
    }

    public function exportRisks(array $filters = [], string $format = 'xlsx'): StreamedResponse
    {
        $query = Risk::with(['project'])
            ->when($filters['project_id'] ?? null, fn($q, $v) => $q->where('project_id', $v))
            ->when($filters['risk_score'] ?? null, fn($q, $v) => $q->where('risk_score', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v));

        $risks = $query->get();

        $data = $risks->map(function ($risk) {
            return [
                'Code' => $risk->risk_code,
                'Description' => $risk->description,
                'Projet' => $risk->project?->name,
                'Type' => $risk->type,
                'Probabilite' => $risk->probability,
                'Impact' => $risk->impact,
                'Score' => $risk->risk_score,
                'Statut' => $risk->status,
                'Proprietaire' => $risk->owner,
                'Plan mitigation' => $risk->mitigation_plan,
                'Identifie le' => $risk->identified_at?->format('d/m/Y'),
            ];
        });

        return $this->generateCsv($data, 'risks_export_' . now()->format('Y-m-d'));
    }

    public function exportChangeRequests(array $filters = [], string $format = 'xlsx'): StreamedResponse
    {
        $query = ChangeRequest::with(['project', 'requestedBy', 'approvedBy'])
            ->when($filters['project_id'] ?? null, fn($q, $v) => $q->where('project_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v));

        $changes = $query->get();

        $data = $changes->map(function ($change) {
            return [
                'Code' => $change->change_code,
                'Titre' => $change->title,
                'Description' => $change->description,
                'Projet' => $change->project?->name,
                'Type' => $change->change_type,
                'Priorite' => $change->priority,
                'Statut' => $change->status,
                'Demande par' => $change->requestedBy?->name,
                'Approuve par' => $change->approvedBy?->name,
                'Impact cout' => $change->cost_impact,
                'Impact planning (jours)' => $change->schedule_impact,
                'Demande le' => $change->requested_at?->format('d/m/Y'),
            ];
        });

        return $this->generateCsv($data, 'changes_export_' . now()->format('Y-m-d'));
    }

    public function exportDashboardPdf(): StreamedResponse
    {
        $dashboardService = app(DashboardService::class);

        $kpis = $dashboardService->getKpis();

        $data = collect([
            ['Indicateur' => 'Total Projets', 'Valeur' => $kpis['total_projects']],
            ['Indicateur' => 'Projets Deployes', 'Valeur' => $kpis['deployed']['count'] ?? 0],
            ['Indicateur' => 'Projets En Cours', 'Valeur' => $kpis['in_progress']['count'] ?? 0],
            ['Indicateur' => 'Risques Critiques', 'Valeur' => $kpis['critical_risks']],
            ['Indicateur' => 'Changes En Attente', 'Valeur' => $kpis['pending_changes']],
        ]);

        return $this->generateCsv($data, 'dashboard_export_' . now()->format('Y-m-d'));
    }

    private function generateCsv($data, string $filename): StreamedResponse
    {
        if ($data->isEmpty()) {
            return response()->streamDownload(function () {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Aucune donnee']);
                fclose($file);
            }, $filename . '.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        $headers = array_keys($data->first());
        $rows = $data->toArray();

        return response()->streamDownload(function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
