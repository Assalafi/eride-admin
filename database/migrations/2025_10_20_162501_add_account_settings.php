<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new settings for account management
        DB::table('system_settings')->insert([
            [
                'key' => 'company_account_balance',
                'value' => '0',
                'type' => 'number',
                'description' => 'Current company account balance (in Naira)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'debit_approval_threshold',
                'value' => '100000',
                'type' => 'number',
                'description' => 'Debit requests above this amount require admin/CEO approval (in Naira)',
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
        DB::table('system_settings')
            ->whereIn('key', ['company_account_balance', 'debit_approval_threshold'])
            ->delete();
    }
};
