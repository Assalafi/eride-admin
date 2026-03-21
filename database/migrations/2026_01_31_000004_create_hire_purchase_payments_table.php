<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hire_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hire_purchase_contract_id')->constrained('hire_purchase_contracts')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            // Payment Details
            $table->integer('payment_number'); // Which payment in sequence (1, 2, 3...)
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0); // amount_paid + penalty
            
            // Balance Tracking
            $table->decimal('balance_before', 12, 2); // Contract balance before this payment
            $table->decimal('balance_after', 12, 2); // Contract balance after this payment
            
            // Dates
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->integer('days_late')->default(0);
            
            // Status
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->default('pending');
            $table->string('payment_method')->nullable(); // cash, transfer, pos, etc.
            $table->string('payment_proof')->nullable();
            $table->text('notes')->nullable();
            
            // Audit
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['hire_purchase_contract_id', 'status']);
            $table->index(['driver_id', 'due_date']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_purchase_payments');
    }
};
