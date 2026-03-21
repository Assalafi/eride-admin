<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_hire_purchase_payment')->default(false)->after('status');
            $table->foreignId('hire_purchase_contract_id')->nullable()->after('is_hire_purchase_payment')
                ->constrained('hire_purchase_contracts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['hire_purchase_contract_id']);
            $table->dropColumn(['is_hire_purchase_payment', 'hire_purchase_contract_id']);
        });
    }
};
