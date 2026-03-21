<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddAccountPrivilegeRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create account management permissions if they don't exist
        $accountPermissions = [
            'view company account',
            'create debit request',
            'view debit requests',
            'approve debit requests',
            'manage account settings',
            'view account transactions',
            'export account reports',
        ];

        foreach ($accountPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Account permissions created successfully!');

        // Create Accountant role if it doesn't exist
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions([
            'view company account',
            'create debit request',
            'view debit requests',
            'view account transactions',
            'view hire purchase',
        ]);

        $this->command->info('Accountant role created successfully!');

        // Update Super Admin role to include all account permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view company account',
                'create debit request',
                'view debit requests',
                'approve debit requests',
                'manage account settings',
                'view account transactions',
                'export account reports',
            ]);
            $this->command->info('Super Admin role updated with account permissions!');
        }

        // Update Branch Manager role to include view and create permissions
        $branchManager = Role::where('name', 'Branch Manager')->first();
        if ($branchManager) {
            $branchManager->givePermissionTo([
                'view company account',
                'create debit request',
                'view debit requests',
                'view account transactions',
            ]);
            $this->command->info('Branch Manager role updated with account permissions!');
        }

        $this->command->info('All roles updated successfully with account privileges!');
    }
}
