<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hire_purchase_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('contract_number')->unique();
            
            // Vehicle Purchase Details
            $table->decimal('vehicle_price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2); // Total to be paid (vehicle_price - down_payment + interest if any)
            
            // Payment Plan
            $table->decimal('daily_payment', 10, 2); // Amount to pay daily
            $table->decimal('weekly_payment', 10, 2)->nullable(); // Alternative weekly payment
            $table->decimal('monthly_payment', 10, 2)->nullable(); // Alternative monthly payment
            $table->enum('payment_frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->integer('total_payment_days'); // Total days/periods to complete payment
            $table->integer('grace_period_days')->default(0); // Grace period before penalties
            
            // Payment Tracking
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('total_balance', 12, 2); // Remaining balance
            $table->integer('payments_made')->default(0); // Number of payments made
            $table->integer('payments_remaining'); // Number of payments remaining
            $table->integer('missed_payments')->default(0); // Count of missed payments
            $table->integer('late_payments')->default(0); // Count of late payments
            
            // Penalty Settings
            $table->decimal('late_fee_percentage', 5, 2)->default(0); // % penalty for late payment
            $table->decimal('late_fee_fixed', 10, 2)->default(0); // Fixed amount penalty
            $table->decimal('total_penalties', 12, 2)->default(0); // Accumulated penalties
            
            // Dates
            $table->date('start_date');
            $table->date('expected_end_date');
            $table->date('actual_end_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_due')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'active', 'completed', 'defaulted', 'terminated', 'suspended'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('termination_reason')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['driver_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['status', 'next_payment_due']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_purchase_contracts');
    }
};
