<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->decimal('minimum_amount', 12, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->dropColumn('minimum_amount');
        });
    }
};
