<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Category;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;

class TranslateExistingDataSeeder extends Seeder
{
    public function run(): void
    {
        // Migrate Projects
        DB::table('projects')->whereNull('name_translations')->chunkById(100, function ($projects) {
            foreach ($projects as $project) {
                DB::table('projects')->where('id', $project->id)->update([
                    'name_translations' => json_encode([
                        'fr' => $project->name,
                        'en' => $this->translateToEnglish($project->name)
                    ]),
                    'description_translations' => $project->description ? json_encode([
                        'fr' => $project->description,
                        'en' => $this->translateToEnglish($project->description)
                    ]) : null,
                    'current_progress_translations' => $project->current_progress ? json_encode([
                        'fr' => $project->current_progress,
                        'en' => $this->translateToEnglish($project->current_progress)
                    ]) : null,
                    'blockers_translations' => $project->blockers ? json_encode([
                        'fr' => $project->blockers,
                        'en' => $this->translateToEnglish($project->blockers)
                    ]) : null,
                ]);
            }
        });

        // Migrate Categories
        DB::table('categories')->whereNull('name_translations')->chunkById(100, function ($categories) {
            foreach ($categories as $category) {
                DB::table('categories')->where('id', $category->id)->update([
                    'name_translations' => json_encode([
                        'fr' => $category->name,
                        'en' => $this->translateToEnglish($category->name)
                    ]),
                ]);
            }
        });

        // Migrate Risks
        DB::table('risks')->whereNull('description_translations')->chunkById(100, function ($risks) {
            foreach ($risks as $risk) {
                DB::table('risks')->where('id', $risk->id)->update([
                    'description_translations' => $risk->description ? json_encode([
                        'fr' => $risk->description,
                        'en' => $this->translateToEnglish($risk->description)
                    ]) : null,
                    'mitigation_plan_translations' => $risk->mitigation_plan ? json_encode([
                        'fr' => $risk->mitigation_plan,
                        'en' => $this->translateToEnglish($risk->mitigation_plan)
                    ]) : null,
                ]);
            }
        });

        // Migrate Change Requests
        DB::table('change_requests')->whereNull('description_translations')->chunkById(100, function ($changes) {
            foreach ($changes as $change) {
                DB::table('change_requests')->where('id', $change->id)->update([
                    'description_translations' => $change->description ? json_encode([
                        'fr' => $change->description,
                        'en' => $this->translateToEnglish($change->description)
                    ]) : null,
                ]);
            }
        });

        // Migrate Comments
        DB::table('comments')->whereNull('content_translations')->chunkById(100, function ($comments) {
            foreach ($comments as $comment) {
                DB::table('comments')->where('id', $comment->id)->update([
                    'content_translations' => json_encode([
                        'fr' => $comment->content,
                        'en' => $this->translateToEnglish($comment->content)
                    ]),
                ]);
            }
        });

        $this->command->info('✅ Existing data translated successfully!');
    }

    /**
     * Simple translation mapping (French to English)
     * Pour une vraie traduction, utiliser une API comme DeepL ou Google Translate
     */
    private function translateToEnglish(string $text): string
    {
        // Dictionnaire de base pour les termes communs
        $translations = [
            // Terms communs
            'Prêt' => 'Ready',
            'En cours' => 'In Progress',
            'Terminé' => 'Completed',
            'Bloqué' => 'Blocked',
            'Paiement' => 'Payment',
            'Crédit' => 'Credit',
            'Carte' => 'Card',
            'Compte' => 'Account',
            'Client' => 'Customer',
            'Service' => 'Service',
            'Promotion' => 'Promotion',
            'Points' => 'Points',
            'Fidélité' => 'Loyalty',
            'Mise à jour' => 'Update',
            'Interface' => 'Interface',
            'Support' => 'Support',
            'Fonctionnalité' => 'Functionality',
            'Analytique' => 'Analytics',
            'Tableau de bord' => 'Dashboard',
            'Reporting' => 'Reporting',
            'Intégration' => 'Integration',
            'Développement' => 'Development',
            'Phase' => 'Phase',
            'test' => 'testing',
            'déploiement' => 'deployment',
            'prêt pour' => 'ready for',
            'en attente' => 'pending',
        ];

        // Remplace les termes connus
        $result = $text;
        foreach ($translations as $fr => $en) {
            $result = str_ireplace($fr, $en, $result);
        }

        return $result;
    }
}
