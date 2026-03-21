<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->date('date');
            $table->decimal('required_payment', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->enum('status', ['due', 'paid', 'partially_paid'])->default('due');
            $table->timestamps();
            
            $table->unique(['driver_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_ledgers');
    }
};
