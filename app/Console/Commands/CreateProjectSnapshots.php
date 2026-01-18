<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectSnapshot;
use Illuminate\Console\Command;

class CreateProjectSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-project-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer des snapshots quotidiens de tous les projets pour analyser les tendances';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projects = Project::with('phases', 'risks', 'changeRequests')->get();

        $today = now()->startOfDay();
        $created = 0;

        foreach ($projects as $project) {
            // Vérifier si un snapshot existe déjà aujourd'hui
            $existingSnapshot = ProjectSnapshot::where('project_id', $project->id)
                ->where('snapshot_date', $today)
                ->first();

            if ($existingSnapshot) {
                $this->line("Snapshot existant pour {$project->name}");
                continue;
            }

            // Créer le snapshot
            ProjectSnapshot::create([
                'project_id' => $project->id,
                'dev_status' => $project->dev_status,
                'rag_status' => $project->rag_status,
                'completion_percent' => $project->completion_percent,
                'active_risks_count' => $project->risks->where('status', 'active')->count(),
                'pending_changes_count' => $project->changeRequests->where('status', 'pending')->count(),
                'completed_phases_count' => $project->phases->where('status', 'completed')->count(),
                'total_phases_count' => $project->phases->count(),
                'snapshot_date' => $today,
            ]);

            $created++;
            $this->line("✓ Snapshot créé pour {$project->name}");
        }

        $this->info("$created snapshots créés avec succès!");
        return Command::SUCCESS;
    }
}
