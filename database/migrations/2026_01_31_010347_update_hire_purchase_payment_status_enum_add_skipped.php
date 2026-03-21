<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hire_purchase_payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('hire_purchase_payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived', 'skipped'])->default('pending')->after('days_late');
        });
    }

    public function down(): void
    {
        Schema::table('hire_purchase_payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('hire_purchase_payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->default('pending')->after('days_late');
        });
    }
};
