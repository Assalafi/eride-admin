<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->string('environment', 10)->default('live')->after('gateway');
            $table->index(['gateway', 'environment', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_gateway_transactions_gateway_environment_status_index');
            $table->dropColumn('environment');
        });
    }
};
