<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Générer un rapport complet pour un projet
     */
    public function generateProjectReport(Project $project): array
    {
        $project->load('category', 'phases', 'risks', 'changeRequests', 'activities');

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->project_code,
                'description' => $project->description,
                'category' => $project->category?->name,
                'status' => $project->dev_status,
                'rag_status' => $project->rag_status,
                'completion' => $project->completion_percent,
                'target_date' => $project->target_date,
                'submission_date' => $project->submission_date,
            ],
            'overview' => [
                'total_phases' => $project->phases->count(),
                'completed_phases' => $project->phases->where('status', 'completed')->count(),
                'total_risks' => $project->risks->count(),
                'high_risks' => $project->risks->where('risk_score', '>=', 16)->count(),
                'total_changes' => $project->changeRequests->count(),
                'pending_changes' => $project->changeRequests->where('status', 'pending')->count(),
            ],
            'phases' => $this->getPhaseDetails($project),
            'risks' => $this->getRiskDetails($project),
            'changes' => $this->getChangeDetails($project),
            'trends' => $this->getTrendData($project->id),
            'forecast' => $this->getForecast($project),
            'generated_at' => now(),
        ];
    }

    /**
     * Générer rapport portfolio complet
     */
    public function generatePortfolioReport(): array
    {
        $projects = Project::with('category', 'risks', 'changeRequests', 'phases')->get();

        return [
            'summary' => [
                'total_projects' => $projects->count(),
                'green_projects' => $projects->where('rag_status', 'green')->count(),
                'amber_projects' => $projects->where('rag_status', 'amber')->count(),
                'red_projects' => $projects->where('rag_status', 'red')->count(),
                'avg_completion' => $projects->avg('completion_percent'),
                'total_risks' => $projects->sum(fn($p) => $p->risks->count()),
                'high_risk_count' => $projects->sum(fn($p) => $p->risks->where('risk_score', '>=', 16)->count()),
            ],
            'projects' => $projects->map(function ($project) {
                return [
                    'name' => $project->name,
                    'code' => $project->project_code,
                    'status' => $project->dev_status,
                    'rag_status' => $project->rag_status,
                    'completion' => $project->completion_percent,
                    'risks' => $project->risks->count(),
                    'changes' => $project->changeRequests->count(),
                ];
            })->toArray(),
            'by_category' => $this->getPortfolioByCategory($projects),
            'by_status' => $this->getPortfolioByStatus($projects),
            'generated_at' => now(),
        ];
    }

    /**
     * Obtenir les tendances RAG et completion pour un projet (derniers 30 jours)
     */
    public function getTrendData(int $projectId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $snapshots = ProjectSnapshot::where('project_id', $projectId)
            ->where('snapshot_date', '>=', $startDate)
            ->orderBy('snapshot_date')
            ->get();

        return [
            'dates' => $snapshots->pluck('snapshot_date')->map(fn($d) => $d->format('Y-m-d')),
            'completion_percent' => $snapshots->pluck('completion_percent'),
            'rag_status' => $snapshots->pluck('rag_status'),
            'rag_colors' => $snapshots->pluck('rag_status')->map(fn($rag) => $this->ragColor($rag)),
        ];
    }

    /**
     * Prévoir la date de fin basée sur la tendance de complétion
     * Utilise régression linéaire simple
     */
    public function getForecast(Project $project): array
    {
        $snapshots = ProjectSnapshot::where('project_id', $project->id)
            ->where('snapshot_date', '>=', now()->subDays(60))
            ->orderBy('snapshot_date')
            ->get();

        if ($snapshots->count() < 2) {
            return [];
        }

        // Préparer les données (jours écoulés vs completion %)
        $data = [];
        $startDate = $snapshots->first()->snapshot_date;

        foreach ($snapshots as $snapshot) {
            $daysElapsed = $startDate->diffInDays($snapshot->snapshot_date);
            $data[] = [
                'x' => $daysElapsed,
                'y' => $snapshot->completion_percent,
            ];
        }

        // Régression linéaire simple: y = a*x + b
        $regression = $this->linearRegression($data);

        if ($regression['slope'] <= 0) {
            return [
                'predicted_date' => null,
                'target_date' => $project->target_date ? $project->target_date->format('Y-m-d') : null,
                'current_percent' => $project->completion_percent,
                'velocity' => 0,
                'days_remaining' => null,
                'days_buffer' => null,
                'confidence_level' => 'Low',
                'data_points' => $snapshots->count(),
                'at_risk' => true,
                'analysis_message' => 'Aucune progression détectée. Vérifiez les mises à jour du projet.',
                'project_start_date' => $project->created_at ? $project->created_at->format('Y-m-d') : now()->format('Y-m-d'),
            ];
        }

        // Calculer jours pour atteindre 100%
        $currentPercent = $project->completion_percent ?? 0;
        $daysNeeded = ($currentPercent < 100) ? (100 - $currentPercent) / $regression['slope'] : 0;
        $predictedDate = now()->addDays((int)$daysNeeded);

        $targetDate = $project->target_date ?? now()->addDays(30);
        $daysBuffer = $targetDate->diffInDays($predictedDate);
        $atRisk = $daysBuffer < 0;

        // Determine confidence level based on data points
        $confidenceLevel = match(true) {
            $snapshots->count() >= 30 => 'High',
            $snapshots->count() >= 10 => 'Medium',
            default => 'Low',
        };

        $analysisMessage = $atRisk
            ? "Risque de retard estimé de " . abs($daysBuffer) . " jours. Vitesse actuelle: " . round($regression['slope'], 2) . "%/jour"
            : "Prévision en avance de " . $daysBuffer . " jours. Vitesse actuelle: " . round($regression['slope'], 2) . "%/jour";

        return [
            'predicted_date' => $predictedDate->format('Y-m-d'),
            'target_date' => $targetDate->format('Y-m-d'),
            'current_percent' => $currentPercent,
            'velocity' => round($regression['slope'], 2),
            'days_remaining' => max(0, (int)$daysNeeded),
            'days_buffer' => $daysBuffer,
            'confidence_level' => $confidenceLevel,
            'data_points' => $snapshots->count(),
            'at_risk' => $atRisk,
            'analysis_message' => $analysisMessage,
            'project_start_date' => $project->created_at ? $project->created_at->format('Y-m-d') : now()->format('Y-m-d'),
        ];
    }

    /**
     * Régression linéaire simple
     */
    private function linearRegression(array $data): array
    {
        $n = count($data);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => 0];
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $point) {
            $sumX += $point['x'];
            $sumY += $point['y'];
            $sumXY += $point['x'] * $point['y'];
            $sumX2 += $point['x'] ** 2;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Détails phases
     */
    private function getPhaseDetails(Project $project): array
    {
        return $project->phases->map(function ($phase) {
            return [
                'name' => $phase->name,
                'status' => $phase->status,
                'start_date' => $phase->start_date,
                'end_date' => $phase->end_date,
            ];
        })->toArray();
    }

    /**
     * Détails risques
     */
    private function getRiskDetails(Project $project): array
    {
        return $project->risks->map(function ($risk) {
            return [
                'code' => $risk->risk_code,
                'type' => $risk->type,
                'description' => $risk->description,
                'score' => $risk->risk_score,
                'status' => $risk->status,
                'mitigation' => $risk->mitigation_plan,
            ];
        })->toArray();
    }

    /**
     * Détails changements
     */
    private function getChangeDetails(Project $project): array
    {
        return $project->changeRequests->map(function ($change) {
            return [
                'code' => $change->change_code,
                'type' => $change->change_type,
                'description' => $change->description,
                'status' => $change->status,
                'requested_at' => $change->requested_at,
            ];
        })->toArray();
    }

    /**
     * Portfolio par catégorie
     */
    private function getPortfolioByCategory(Collection $projects): array
    {
        return $projects->groupBy('category.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'green' => $group->where('rag_status', 'green')->count(),
                'amber' => $group->where('rag_status', 'amber')->count(),
                'red' => $group->where('rag_status', 'red')->count(),
                'avg_completion' => $group->avg('completion_percent'),
            ];
        })->toArray();
    }

    /**
     * Portfolio par statut
     */
    private function getPortfolioByStatus(Collection $projects): array
    {
        return $projects->groupBy('dev_status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'green' => $group->where('rag_status', 'green')->count(),
                'amber' => $group->where('rag_status', 'amber')->count(),
                'red' => $group->where('rag_status', 'red')->count(),
            ];
        })->toArray();
    }

    /**
     * Couleur hex pour RAG status
     */
    private function ragColor(string $status): string
    {
        return match ($status) {
            'green' => '#10b981',
            'amber' => '#f59e0b',
            'red' => '#ef4444',
            default => '#6b7280',
        };
    }
}
