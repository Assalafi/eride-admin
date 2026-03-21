<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'system_name',
                'value' => 'eRide Transport Management',
                'type' => 'text',
                'description' => 'The name of the system displayed throughout the application',
            ],
            [
                'key' => 'company_email',
                'value' => 'info@eride.ng',
                'type' => 'text',
                'description' => 'Primary contact email address',
            ],
            [
                'key' => 'company_phone',
                'value' => '+234 000 000 0000',
                'type' => 'text',
                'description' => 'Primary contact phone number',
            ],
            [
                'key' => 'company_address',
                'value' => 'Nigeria',
                'type' => 'text',
                'description' => 'Company physical address',
            ],
            
            // Financial Settings
            [
                'key' => 'daily_remittance_amount',
                'value' => '5000.00',
                'type' => 'number',
                'description' => 'Default daily remittance amount expected from drivers',
            ],
            [
                'key' => 'charging_cost_per_session',
                'value' => '2000.00',
                'type' => 'number',
                'description' => 'Cost per charging session',
            ],
            
            // System Preferences
            [
                'key' => 'enable_maintenance',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable or disable maintenance request module',
            ],
            [
                'key' => 'enable_notifications',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable or disable system notifications',
            ],
            [
                'key' => 'require_manager_approval',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require manager approval for maintenance requests',
            ],
            
            // Logos (will be set via upload)
            [
                'key' => 'system_logo',
                'value' => null,
                'type' => 'file',
                'description' => 'System logo displayed in sidebar and login page',
            ],
            [
                'key' => 'system_favicon',
                'value' => null,
                'type' => 'file',
                'description' => 'Browser favicon',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
