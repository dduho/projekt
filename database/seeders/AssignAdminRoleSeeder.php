<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Créer le rôle admin s'il n'existe pas
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Assigner le rôle au premier utilisateur
        $user = User::first();
        if ($user && !$user->hasRole('admin')) {
            $user->assignRole('admin');
            echo "✅ Role admin assigned to {$user->name}\n";
        } else {
            echo "ℹ️  User already has admin role\n";
        }
    }
}
