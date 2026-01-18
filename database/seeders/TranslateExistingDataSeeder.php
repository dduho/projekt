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
                        'en' => $project->name,
                        'fr' => $this->translateToEnglish($project->name)
                    ]),
                    'description_translations' => $project->description ? json_encode([
                        'en' => $project->description,
                        'fr' => $this->translateToEnglish($project->description)
                    ]) : null,
                    'current_progress_translations' => $project->current_progress ? json_encode([
                        'en' => $project->current_progress,
                        'fr' => $this->translateToEnglish($project->current_progress)
                    ]) : null,
                    'blockers_translations' => $project->blockers ? json_encode([
                        'en' => $project->blockers,
                        'fr' => $this->translateToEnglish($project->blockers)
                    ]) : null,
                ]);
            }
        });

        // Migrate Categories
        DB::table('categories')->whereNull('name_translations')->chunkById(100, function ($categories) {
            foreach ($categories as $category) {
                DB::table('categories')->where('id', $category->id)->update([
                    'name_translations' => json_encode([
                        'en' => $category->name,
                        'fr' => $this->translateToEnglish($category->name)
                    ]),
                ]);
            }
        });

        // Migrate Risks
        DB::table('risks')->whereNull('description_translations')->chunkById(100, function ($risks) {
            foreach ($risks as $risk) {
                DB::table('risks')->where('id', $risk->id)->update([
                    'description_translations' => $risk->description ? json_encode([
                        'en' => $risk->description,
                        'fr' => $this->translateToEnglish($risk->description)
                    ]) : null,
                    'mitigation_plan_translations' => $risk->mitigation_plan ? json_encode([
                        'en' => $risk->mitigation_plan,
                        'fr' => $this->translateToEnglish($risk->mitigation_plan)
                    ]) : null,
                ]);
            }
        });

        // Migrate Change Requests
        DB::table('change_requests')->whereNull('description_translations')->chunkById(100, function ($changes) {
            foreach ($changes as $change) {
                DB::table('change_requests')->where('id', $change->id)->update([
                    'description_translations' => $change->description ? json_encode([
                        'en' => $change->description,
                        'fr' => $this->translateToEnglish($change->description)
                    ]) : null,
                ]);
            }
        });

        // Migrate Comments
        DB::table('comments')->whereNull('content_translations')->chunkById(100, function ($comments) {
            foreach ($comments as $comment) {
                DB::table('comments')->where('id', $comment->id)->update([
                    'content_translations' => json_encode([
                        'en' => $comment->content,
                        'fr' => $this->translateToEnglish($comment->content)
                    ]),
                ]);
            }
        });

        $this->command->info('✅ Existing data translated successfully!');
    }

    /**
     * Traduction professionnelle Anglais vers Français
     */
    private function translateToEnglish(string $text): string
    {
        // Dictionnaire complet de traduction EN → FR
        $translations = [
            // Termes techniques
            'Integration' => 'Intégration',
            'integration' => 'intégration',
            'API' => 'API',
            'Bill Payment' => 'Paiement de factures',
            'bill payment' => 'paiement de factures',
            'Payment' => 'Paiement',
            'payment' => 'paiement',
            'Airtime' => 'Crédit téléphonique',
            'airtime' => 'crédit téléphonique',
            'Mobile Money' => 'Mobile Money',
            'Organization' => 'Organisation',
            'organization' => 'organisation',
            'Company' => 'Entreprise',
            'company' => 'entreprise',
            'Merchant' => 'Commerçant',
            'merchant' => 'commerçant',
            'Agent' => 'Agent',
            'agent' => 'agent',
            'Dealer' => 'Revendeur',
            'dealer' => 'revendeur',
            'Insurance' => 'Assurance',
            'insurance' => 'assurance',
            'Invest' => 'Investissement',
            'invest' => 'investissement',
            'Saving' => 'Épargne',
            'saving' => 'épargne',
            'Bank' => 'Banque',
            'bank' => 'banque',
            
            // Status et progression
            'Waiting' => 'En attente',
            'waiting' => 'en attente',
            'Plan for' => 'Prévu pour',
            'plan for' => 'prévu pour',
            'Planned' => 'Planifié',
            'planned' => 'planifié',
            'Signoff' => 'Validation',
            'signoff' => 'validation',
            'None' => 'Aucun',
            'none' => 'aucun',
            'See Progress' => 'Voir progression',
            'see progress' => 'voir progression',
            
            // Actions et verbes
            'allow' => 'permettre',
            'want to' => 'souhaite',
            'can do' => 'peut effectuer',
            'buy' => 'acheter',
            'config' => 'configurer',
            'configure' => 'configurer',
            'manually' => 'manuellement',
            'directly' => 'directement',
            'through' => 'via',
            
            // Conjonctions et prépositions
            'which is' => 'qui est',
            'to be for' => 'pour',
            'end of' => 'fin',
            'at that time' => 'à ce moment-là',
            'for himself' => 'pour lui-même',
            'by himself' => 'par lui-même',
            'via' => 'via',
            
            // Termes spécifiques métier
            'requirement' => 'besoin',
            'requirements' => 'besoins',
            'Team' => 'Équipe',
            'team' => 'équipe',
            'HQ' => 'Siège',
            'APP' => 'Application',
            'USSD' => 'USSD',
            'Corp' => 'Entreprise',
            
            // Mois
            'January' => 'janvier',
            'February' => 'février',
            'february' => 'février',
            'March' => 'mars',
            'April' => 'avril',
            'May' => 'mai',
            'June' => 'juin',
            'July' => 'juillet',
            'August' => 'août',
            'September' => 'septembre',
            'October' => 'octobre',
            'November' => 'novembre',
            'December' => 'décembre',
            
            // Phrases complètes communes
            'Waiting HQ to plan the requirement' => 'En attente que le siège planifie le besoin',
            'not ready for' => 'pas prêt pour',
            'so MOOV postpone to after cutover' => 'donc MOOV reporte après la bascule',
        ];

        $result = $text;
        
        // Remplacer les phrases complètes d'abord
        foreach ($translations as $en => $fr) {
            if (strlen($en) > 20) { // Phrases longues
                $result = str_ireplace($en, $fr, $result);
            }
        }
        
        // Puis les mots individuels
        foreach ($translations as $en => $fr) {
            if (strlen($en) <= 20) {
                $result = str_ireplace($en, $fr, $result);
            }
        }
        
        // Traductions spécifiques de patterns
        $result = preg_replace('/Moov want(s)? to/i', 'Moov souhaite', $result);
        $result = preg_replace('/can\'t/i', 'ne peut pas', $result);
        $result = preg_replace('/don\'t have/i', 'n\'a pas', $result);
        
        return $result;
    }
}
