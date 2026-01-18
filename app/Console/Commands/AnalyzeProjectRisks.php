<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\RiskScoringService;
use Illuminate\Console\Command;

class AnalyzeProjectRisks extends Command
{
    protected $signature = 'projects:analyze-risks {--project= : ID d\'un projet spécifique}';
    protected $description = 'Analyser les projets et générer automatiquement des risques via ML';

    public function handle(RiskScoringService $riskService): int
    {
        $projectId = $this->option('project');

        if ($projectId) {
            $project = Project::findOrFail($projectId);
            $this->analyzeProject($project, $riskService);
        } else {
            $projects = Project::whereNull('deleted_at')->get();
            $this->info("Analyse de {$projects->count()} projets...\n");

            $progressBar = $this->output->createProgressBar($projects->count());

            foreach ($projects as $project) {
                $this->analyzeProject($project, $riskService);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);
        }

        $this->info('✅ Analyse terminée');
        return Command::SUCCESS;
    }

    private function analyzeProject(Project $project, RiskScoringService $riskService): void
    {
        $analysis = $riskService->analyzeProject($project);
        $created = $riskService->createSuggestedRisks($project);

        if ($created > 0) {
            $this->line("  📊 {$project->name}: Score {$analysis['score']} ({$analysis['level']}) - {$created} risque(s) créé(s)");
        }
    }
}
