<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Projects
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'export projects',
            
            // Risks
            'view risks',
            'create risks',
            'edit risks',
            'delete risks',
            'export risks',
            
            // Change Requests
            'view change-requests',
            'create change-requests',
            'edit change-requests',
            'delete change-requests',
            'approve change-requests',
            'reject change-requests',
            'export change-requests',
            
            // Categories
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Import
            'import data',
            
            // Reports
            'view dashboard',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin - Full access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager - Most permissions except user management
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view projects', 'create projects', 'edit projects', 'export projects',
            'view risks', 'create risks', 'edit risks', 'export risks',
            'view change-requests', 'create change-requests', 'edit change-requests', 
            'approve change-requests', 'reject change-requests', 'export change-requests',
            'view categories', 'create categories', 'edit categories',
            'view users',
            'import data',
            'view dashboard', 'view reports', 'export reports',
        ]);

        // User - Read access and basic operations
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view projects',
            'view risks', 'create risks',
            'view change-requests', 'create change-requests',
            'view categories',
            'view dashboard',
        ]);

        // Guest - View only
        $guest = Role::create(['name' => 'guest']);
        $guest->givePermissionTo([
            'view projects',
            'view risks',
            'view change-requests',
            'view categories',
            'view dashboard',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
