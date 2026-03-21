<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create branches
        $branches = [
            ['name' => 'Maiduguri Branch', 'location' => 'Maiduguri, Borno State'],
            ['name' => 'Abuja Branch', 'location' => 'Abuja, FCT'],
            ['name' => 'Lagos Branch', 'location' => 'Lagos, Lagos State'],
        ];

        foreach ($branches as $branchData) {
            Branch::create($branchData);
        }

        // Create Super Admin user
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@eride.com',
            'password' => Hash::make('password'),
            'branch_id' => null, // Super admin has access to all branches
        ]);

        $superAdmin->assignRole('Super Admin');

        // Create a branch manager for Maiduguri branch
        $maiduguriBranch = Branch::where('name', 'Maiduguri Branch')->first();
        
        $branchManager = User::create([
            'name' => 'Maiduguri Manager',
            'email' => 'manager@maiduguri.eride.com',
            'password' => Hash::make('password'),
            'branch_id' => $maiduguriBranch->id,
        ]);

        $branchManager->assignRole('Branch Manager');

        // Create a mechanic for Maiduguri branch
        $mechanic = User::create([
            'name' => 'John Mechanic',
            'email' => 'mechanic@maiduguri.eride.com',
            'password' => Hash::make('password'),
            'branch_id' => $maiduguriBranch->id,
        ]);

        $mechanic->assignRole('Mechanic');

        // Create a storekeeper for Maiduguri branch
        $storekeeper = User::create([
            'name' => 'Ahmed Storekeeper',
            'email' => 'storekeeper@maiduguri.eride.com',
            'password' => Hash::make('password'),
            'branch_id' => $maiduguriBranch->id,
        ]);

        $storekeeper->assignRole('Storekeeper');

        $this->command->info('Initial data created successfully!');
        $this->command->info('Super Admin: admin@eride.com / password');
        $this->command->info('Branch Manager: manager@maiduguri.eride.com / password');
        $this->command->info('Mechanic: mechanic@maiduguri.eride.com / password');
        $this->command->info('Storekeeper: storekeeper@maiduguri.eride.com / password');
    }
}
