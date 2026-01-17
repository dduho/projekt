<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Risk;
use App\Models\ChangeRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run roles and permissions seeder first
        $this->call(RolePermissionSeeder::class);
        
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin PRISM',
            'email' => 'admin@moovmoney.tg',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Create Regular Users
        $users = [
            [
                'name' => 'Jean Dupont',
                'email' => 'jean.dupont@moovmoney.tg',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie.martin@moovmoney.tg',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'Pierre Bernard',
                'email' => 'pierre.bernard@moovmoney.tg',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            $user->assignRole($userData['role']);
        }

        // Create Categories
        $categories = [
            ['name' => 'Mobile Apps', 'description' => 'Applications mobiles', 'color' => '#667eea'],
            ['name' => 'Web Platform', 'description' => 'Plateformes web', 'color' => '#764ba2'],
            ['name' => 'Infrastructure', 'description' => 'Infrastructure IT', 'color' => '#f59e0b'],
            ['name' => 'Security', 'description' => 'Sécurité et conformité', 'color' => '#ef4444'],
            ['name' => 'Data & Analytics', 'description' => 'Données et analyses', 'color' => '#10b981'],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create Projects
        $projects = [
            [
                'project_code' => 'MOOV-001',
                'name' => 'Mobile Banking App v2',
                'description' => 'Nouvelle version de l\'application mobile banking avec fonctionnalités avancées',
                'category_id' => 1,
                'owner_id' => 2,
                'business_area' => 'Payment Services',
                'priority' => 'High',
                'rag_status' => 'Green',
                'dev_status' => 'In Development',
                'frs_status' => 'Signoff',
                'current_progress' => 'Backend API complété, Frontend en cours',
                'blockers' => null,
                'planned_release' => 'v2.0',
                'submission_date' => '2025-10-01',
                'target_date' => '2026-03-31',
                'completion_percent' => 65,
            ],
            [
                'project_code' => 'MOOV-002',
                'name' => 'Dashboard Analytique',
                'description' => 'Tableau de bord pour l\'analyse des transactions',
                'category_id' => 5,
                'owner_id' => 3,
                'business_area' => 'Data & Analytics',
                'priority' => 'High',
                'rag_status' => 'Amber',
                'dev_status' => 'Testing',
                'frs_status' => 'Review',
                'current_progress' => 'Phase de tests en cours',
                'blockers' => 'Intégration API en attente',
                'planned_release' => 'v1.5',
                'submission_date' => '2025-11-01',
                'target_date' => '2026-02-28',
                'completion_percent' => 75,
            ],
            [
                'project_code' => 'MOOV-003',
                'name' => 'Infrastructure Cloud Migration',
                'description' => 'Migration des services vers AWS',
                'category_id' => 3,
                'owner_id' => 4,
                'business_area' => 'Infrastructure',
                'priority' => 'High',
                'rag_status' => 'Red',
                'dev_status' => 'Not Started',
                'frs_status' => 'Draft',
                'current_progress' => 'Planification en cours',
                'blockers' => 'Budget en attente de validation',
                'planned_release' => 'Phase 1',
                'submission_date' => '2025-12-01',
                'target_date' => '2026-06-30',
                'completion_percent' => 25,
            ],
            [
                'project_code' => 'MOOV-004',
                'name' => 'API Gateway v3',
                'description' => 'Nouvelle version de l\'API Gateway avec microservices',
                'category_id' => 2,
                'owner_id' => 2,
                'business_area' => 'Integration',
                'priority' => 'High',
                'rag_status' => 'Green',
                'dev_status' => 'In Development',
                'frs_status' => 'Signoff',
                'current_progress' => 'Microservices déployés, tests en cours',
                'blockers' => null,
                'planned_release' => 'v3.0',
                'submission_date' => '2025-09-15',
                'target_date' => '2026-04-15',
                'completion_percent' => 50,
            ],
            [
                'project_code' => 'MOOV-005',
                'name' => 'Conformité GDPR',
                'description' => 'Mise en conformité GDPR de tous les systèmes',
                'category_id' => 4,
                'owner_id' => 3,
                'business_area' => 'Security & Compliance',
                'priority' => 'Medium',
                'rag_status' => 'Amber',
                'dev_status' => 'Testing',
                'frs_status' => 'Signoff',
                'current_progress' => 'Audit de sécurité en cours',
                'blockers' => null,
                'planned_release' => 'v1.0',
                'submission_date' => '2025-08-01',
                'target_date' => '2026-01-31',
                'completion_percent' => 85,
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::create($projectData);

            // Create some risks for each project
            if ($project->rag_status === 'Red' || $project->rag_status === 'Amber') {
                Risk::create([
                    'project_id' => $project->id,
                    'risk_code' => $project->project_code . '-R01',
                    'type' => 'Risk',
                    'description' => 'Risque de dépassement délai si les dépendances externes ne sont pas livrées à temps',
                    'owner_id' => $project->owner_id,
                    'probability' => 'High',
                    'impact' => 'High',
                    'risk_score' => 'High',
                    'status' => 'Open',
                    'mitigation_plan' => 'Suivi hebdomadaire avec les fournisseurs externes et plan de contingence',
                ]);
            }

            // Create a change request
            if (rand(0, 1)) {
                ChangeRequest::create([
                    'project_id' => $project->id,
                    'change_code' => $project->project_code . '-CHG01',
                    'change_type' => 'Scope',
                    'description' => 'Ajout de l\'authentification biométrique suite à demande client',
                    'requested_by_id' => $admin->id,
                    'approved_by_id' => null,
                    'status' => 'Pending',
                    'requested_at' => now(),
                    'resolved_at' => null,
                ]);
            }
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Login with: admin@moovmoney.tg / password');
    }
}
