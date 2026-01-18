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

        // Catégories, projets, risques et change requests seront importés depuis le fichier Excel
        // Utiliser: php artisan projects:import storage/moov_portfolio.xlsx

        $this->command->info('Database seeded successfully!');
        $this->command->info('Login with: admin@moovmoney.tg / password');
        $this->command->info('Import projects: php artisan projects:import storage/moov_portfolio.xlsx');
    }
}
