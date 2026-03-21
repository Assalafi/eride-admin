<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, number, boolean, file
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'system_name',
                'value' => 'eRide Transport Management',
                'type' => 'text',
                'description' => 'The name of the system displayed throughout the application',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'daily_remittance_amount',
                'value' => '15000',
                'type' => 'number',
                'description' => 'Default daily remittance amount expected from drivers (in Naira)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'charging_per_session',
                'value' => '5000',
                'type' => 'number',
                'description' => 'Amount charged per charging session (in Naira)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_email',
                'value' => 'info@eride.ng',
                'type' => 'text',
                'description' => 'Official company email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_phone',
                'value' => '+234 xxx xxx xxxx',
                'type' => 'text',
                'description' => 'Official company phone number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_address',
                'value' => 'Maiduguri, Borno State, Nigeria',
                'type' => 'text',
                'description' => 'Official company address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_approval_required',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require manager approval for maintenance requests',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'payment_approval_required',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require approval for driver payments',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'NGN',
                'type' => 'text',
                'description' => 'System currency code',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency_symbol',
                'value' => '₦',
                'type' => 'text',
                'description' => 'Currency symbol',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
