<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('gateway', 30)->default('opay');
            $table->string('purpose', 40);
            $table->string('reference', 100)->unique();
            $table->string('gateway_order_no', 100)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 20)->default('INITIAL');
            $table->string('checkout_url', 1000)->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('wallet_funding_request_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'purpose', 'status']);
            $table->index('gateway_transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('wallet_funding_request_id')->references('id')->on('wallet_funding_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
