<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Risk;
use Carbon\Carbon;

class RiskScoringService
{
    /**
     * Analyser un projet et calculer son score de risque global
     */
    public function analyzeProject(Project $project): array
    {
        $indicators = [
            'delay_risk' => $this->calculateDelayRisk($project),
            'blockers_risk' => $this->calculateBlockersRisk($project),
            'changes_risk' => $this->calculateChangesRisk($project),
            'phase_risk' => $this->calculatePhaseRisk($project),
            'po_risk' => $this->calculatePORisk($project),
            'completion_risk' => $this->calculateCompletionRisk($project),
        ];

        $overallScore = $this->calculateOverallScore($indicators);
        $suggestions = $this->generateRiskSuggestions($project, $indicators);

        return [
            'score' => $overallScore,
            'level' => $this->getRiskLevel($overallScore),
            'indicators' => $indicators,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Risque de retard (comparaison date cible vs progression)
     */
    private function calculateDelayRisk(Project $project): float
    {
        if (!$project->target_date) {
            return 0.3; // Pas de date cible = risque moyen
        }

        $daysUntilTarget = Carbon::now()->diffInDays($project->target_date, false);
        $completion = $project->calculated_completion_percent ?? $project->completion_percent ?? 0;

        // Si projet en retard
        if ($daysUntilTarget < 0) {
            return 1.0; // Risque critique
        }

        // Si moins de 30 jours et completion < 70%
        if ($daysUntilTarget < 30 && $completion < 70) {
            return 0.8; // Risque élevé
        }

        // Si moins de 60 jours et completion < 50%
        if ($daysUntilTarget < 60 && $completion < 50) {
            return 0.6; // Risque moyen-élevé
        }

        // Calcul proportionnel
        $expectedCompletion = max(0, 100 - ($daysUntilTarget / 3)); // ~30 jours = 90%
        $gap = max(0, $expectedCompletion - $completion);
        
        return min(1.0, $gap / 100);
    }

    /**
     * Risque lié aux blockers
     */
    private function calculateBlockersRisk(Project $project): float
    {
        if (empty($project->blockers)) {
            return 0.0;
        }

        // Plus le texte est long, plus c'est critique
        $length = strlen($project->blockers);
        
        if ($length > 200) return 0.9;
        if ($length > 100) return 0.7;
        if ($length > 50) return 0.5;
        
        return 0.3;
    }

    /**
     * Risque lié au nombre de change requests
     */
    private function calculateChangesRisk(Project $project): float
    {
        $changesCount = $project->changeRequests()->count();

        if ($changesCount > 10) return 0.9;
        if ($changesCount > 5) return 0.6;
        if ($changesCount > 2) return 0.3;
        
        return 0.0;
    }

    /**
     * Risque lié aux phases bloquées
     */
    private function calculatePhaseRisk(Project $project): float
    {
        $phases = $project->phases;
        if ($phases->isEmpty()) {
            return 0.2; // Pas de phases définies = petit risque
        }

        $blockedCount = $phases->where('status', 'Blocked')->count();
        $pendingCount = $phases->where('status', 'Pending')->count();
        $totalPhases = $phases->count();

        if ($blockedCount > 0) {
            return min(1.0, 0.7 + ($blockedCount * 0.1));
        }

        // Si plus de 50% des phases sont pending et on approche la deadline
        if ($pendingCount / $totalPhases > 0.5 && $project->target_date) {
            $daysLeft = Carbon::now()->diffInDays($project->target_date, false);
            if ($daysLeft < 60) {
                return 0.5;
            }
        }

        return 0.0;
    }

    /**
     * Risque lié au manque de Product Owner
     */
    private function calculatePORisk(Project $project): float
    {
        if ($project->need_po) {
            // Très critique si pas d'owner ET need PO
            if (!$project->owner) {
                return 1.0;
            }
            return 0.7; // Critique même avec owner
        }

        return 0.0;
    }

    /**
     * Risque de completion (stagnation)
     */
    private function calculateCompletionRisk(Project $project): float
    {
        $completion = $project->calculated_completion_percent ?? $project->completion_percent ?? 0;

        // Si projet à 0% depuis longtemps
        if ($completion == 0 && $project->created_at->diffInDays(Carbon::now()) > 30) {
            return 0.8;
        }

        // Si projet stagné entre 20-40%
        if ($completion >= 20 && $completion <= 40) {
            return 0.4;
        }

        return 0.0;
    }

    /**
     * Calculer le score global (moyenne pondérée)
     */
    private function calculateOverallScore(array $indicators): float
    {
        $weights = [
            'delay_risk' => 0.25,
            'blockers_risk' => 0.20,
            'changes_risk' => 0.10,
            'phase_risk' => 0.20,
            'po_risk' => 0.15,
            'completion_risk' => 0.10,
        ];

        $score = 0;
        foreach ($indicators as $key => $value) {
            $score += $value * ($weights[$key] ?? 0);
        }

        return round($score, 2);
    }

    /**
     * Déterminer le niveau de risque
     */
    private function getRiskLevel(float $score): string
    {
        if ($score >= 0.7) return 'Critical';
        if ($score >= 0.5) return 'High';
        if ($score >= 0.3) return 'Medium';
        return 'Low';
    }

    /**
     * Générer des suggestions de risques automatiques
     */
    private function generateRiskSuggestions(Project $project, array $indicators): array
    {
        $suggestions = [];

        // Risque de retard
        if ($indicators['delay_risk'] >= 0.6) {
            $suggestions[] = [
                'title' => 'Risque de retard sur le planning',
                'description' => 'Le projet accuse un retard par rapport à la date cible. Révision du planning recommandée.',
                'score' => $this->getRiskLevel($indicators['delay_risk']),
                'category' => 'Planning',
            ];
        }

        // Blockers détectés
        if ($indicators['blockers_risk'] >= 0.5) {
            $suggestions[] = [
                'title' => 'Blocants identifiés',
                'description' => 'Des blocants significatifs sont présents : ' . substr($project->blockers, 0, 100),
                'score' => $this->getRiskLevel($indicators['blockers_risk']),
                'category' => 'Blocage',
            ];
        }

        // Trop de changes
        if ($indicators['changes_risk'] >= 0.6) {
            $count = $project->changeRequests()->count();
            $suggestions[] = [
                'title' => 'Nombre élevé de demandes de changement',
                'description' => "{$count} demandes de changement enregistrées. Risque de scope creep.",
                'score' => $this->getRiskLevel($indicators['changes_risk']),
                'category' => 'Périmètre',
            ];
        }

        // Phases bloquées
        if ($indicators['phase_risk'] >= 0.7) {
            $suggestions[] = [
                'title' => 'Phases du projet bloquées',
                'description' => 'Une ou plusieurs phases sont bloquées. Action immédiate requise.',
                'score' => 'Critical',
                'category' => 'Exécution',
            ];
        }

        // Manque de PO
        if ($indicators['po_risk'] >= 0.7) {
            $suggestions[] = [
                'title' => 'Product Owner manquant',
                'description' => 'Le projet nécessite un Product Owner. Risque de décisions non prises.',
                'score' => 'High',
                'category' => 'Ressources',
            ];
        }

        // Projet stagnant
        if ($indicators['completion_risk'] >= 0.5) {
            $suggestions[] = [
                'title' => 'Projet en stagnation',
                'description' => 'Peu ou pas de progrès détecté. Vérifier l\'allocation des ressources.',
                'score' => $this->getRiskLevel($indicators['completion_risk']),
                'category' => 'Progression',
            ];
        }

        return $suggestions;
    }

    /**
     * Créer automatiquement des risques suggérés
     */
    public function createSuggestedRisks(Project $project): int
    {
        $analysis = $this->analyzeProject($project);
        $created = 0;

        foreach ($analysis['suggestions'] as $suggestion) {
            // Vérifier si un risque similaire existe déjà
            $exists = Risk::where('project_id', $project->id)
                ->where('title', $suggestion['title'])
                ->where('status', '!=', 'Closed')
                ->exists();

            if (!$exists) {
                Risk::create([
                    'project_id' => $project->id,
                    'title' => $suggestion['title'],
                    'description' => $suggestion['description'],
                    'risk_score' => $suggestion['score'],
                    'status' => 'Open',
                    'category' => $suggestion['category'] ?? 'Autre',
                    'identified_date' => Carbon::now(),
                    'auto_generated' => true, // Flag pour identifier les risques auto
                ]);
                $created++;
            }
        }

        return $created;
    }
}
