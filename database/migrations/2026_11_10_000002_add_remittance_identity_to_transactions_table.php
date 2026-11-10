<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('remittance_date')->nullable()->after('created_at');
            $table->string('remittance_key', 100)->nullable()->after('remittance_date');
        });

        // Keep the first historical row as the canonical identity. Existing
        // duplicates intentionally remain untouched and have a NULL key so
        // the unique index can be added without deleting financial history.
        DB::statement("\n            UPDATE transactions\n            SET remittance_date = DATE(created_at)\n            WHERE type = 'daily_remittance'\n              AND created_at IS NOT NULL\n        ");

        DB::statement("\n            UPDATE transactions AS t\n            INNER JOIN (\n                SELECT MIN(id) AS id, driver_id, DATE(created_at) AS remittance_date\n                FROM transactions\n                WHERE type = 'daily_remittance'\n                  AND created_at IS NOT NULL\n                GROUP BY driver_id, DATE(created_at)\n            ) AS canonical ON canonical.id = t.id\n            SET t.remittance_key = CONCAT(t.driver_id, ':daily_remittance:', canonical.remittance_date)\n            WHERE t.type = 'daily_remittance'\n        ");

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('remittance_key', 'transactions_remittance_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_remittance_key_unique');
            $table->dropColumn(['remittance_date', 'remittance_key']);
        });
    }
};
