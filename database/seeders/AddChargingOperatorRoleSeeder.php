<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddChargingOperatorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create new charging permissions if they don't exist
        $chargingPermissions = [
            'view charging requests',
            'approve charging requests',
            'complete charging requests',
        ];

        foreach ($chargingPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Charging Station Operator role if it doesn't exist
        $chargingOperator = Role::firstOrCreate(['name' => 'Charging Station Operator']);
        $chargingOperator->syncPermissions([
            'view charging requests',
            'complete charging requests',
        ]);

        $this->command->info('Charging Station Operator role created successfully!');

        // Update Branch Manager role to include charging permissions
        $branchManager = Role::where('name', 'Branch Manager')->first();
        if ($branchManager) {
            $branchManager->givePermissionTo([
                'view charging requests',
                'approve charging requests',
            ]);
            $this->command->info('Branch Manager role updated with charging permissions!');
        }

        // Update Super Admin role to include all new permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
            $this->command->info('Super Admin role updated with all permissions!');
        }

        $this->command->info('All charging permissions and roles configured successfully!');
    }
}
