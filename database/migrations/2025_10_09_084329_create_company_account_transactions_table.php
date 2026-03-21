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
        Schema::create('company_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('type', ['income', 'expense'])->comment('income=credit, expense=debit');
            $table->decimal('amount', 12, 2);
            $table->string('category')->comment('e.g., daily_remittance, fuel, maintenance, salary, etc.');
            $table->string('reference')->nullable()->comment('Reference to related record');
            $table->text('description');
            $table->date('transaction_date');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->string('receipt_document')->nullable()->comment('Path to receipt/document');
            $table->timestamps();
            
            $table->index(['branch_id', 'type']);
            $table->index('transaction_date');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_account_transactions');
    }
};
