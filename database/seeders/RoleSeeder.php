<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Branch management
            'view branches',
            'manage branches',
            
            // Driver management
            'view drivers',
            'create drivers',
            'edit drivers',
            'delete drivers',
            
            // Vehicle management
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'delete vehicles',
            
            // Vehicle assignments
            'assign vehicles',
            'return vehicles',
            
            // Payment management
            'view payments',
            'approve payments',
            'reject payments',
            
            // Hire Purchase management
            'view hire purchase',
            'create hire purchase',
            'edit hire purchase',
            'delete hire purchase',
            
            // Maintenance management
            'create maintenance requests',
            'view maintenance requests',
            'approve maintenance requests',
            'complete maintenance requests',
            
            // Charging management
            'view charging requests',
            'approve charging requests',
            'complete charging requests',
            
            // Inventory management
            'view inventory',
            'manage inventory',
            
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Branch Manager
        $branchManager = Role::create(['name' => 'Branch Manager']);
        $branchManager->givePermissionTo([
            'view drivers',
            'create drivers',
            'edit drivers',
            'delete drivers',
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'delete vehicles',
            'assign vehicles',
            'return vehicles',
            'view payments',
            'approve payments',
            'reject payments',
            'view hire purchase',
            'create hire purchase',
            'edit hire purchase',
            'view maintenance requests',
            'approve maintenance requests',
            'view charging requests',
            'approve charging requests',
            'view inventory',
            'manage inventory',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ]);

        // Mechanic
        $mechanic = Role::create(['name' => 'Mechanic']);
        $mechanic->givePermissionTo([
            'view inventory',
            'create maintenance requests',
            'view maintenance requests',
        ]);

        // Storekeeper
        $storekeeper = Role::create(['name' => 'Storekeeper']);
        $storekeeper->givePermissionTo([
            'view inventory',
            'view maintenance requests',
            'complete maintenance requests',
        ]);

        // Charging Station Operator
        $chargingOperator = Role::create(['name' => 'Charging Station Operator']);
        $chargingOperator->givePermissionTo([
            'view charging requests',
            'complete charging requests',
        ]);

        // Driver
        $driver = Role::create(['name' => 'Driver']);
        $driver->givePermissionTo([
            'view payments',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
