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
        Schema::table('transactions', function (Blueprint $table) {
            // Change type from ENUM to VARCHAR for flexibility
            $table->string('type', 50)->change();
            
            // Add missing columns for comprehensive transaction handling
            $table->string('reference', 100)->nullable()->after('amount');
            $table->text('description')->nullable()->after('reference');
            $table->foreignId('processed_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable()->after('processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Remove added columns
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['reference', 'description', 'processed_by', 'processed_at']);
            
            // Revert type back to ENUM
            $table->enum('type', ['daily_remittance', 'maintenance_debit', 'wallet_top_up'])->change();
        });
    }
};
