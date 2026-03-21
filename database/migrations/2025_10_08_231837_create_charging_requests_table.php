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
        Schema::create('charging_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->decimal('battery_level_before', 5, 2)->nullable()->comment('Battery level before charging (%)');
            $table->decimal('battery_level_after', 5, 2)->nullable()->comment('Battery level after charging (%)');
            $table->decimal('energy_consumed', 10, 2)->nullable()->comment('Energy consumed in kWh');
            $table->decimal('charging_cost', 10, 2)->comment('Cost of charging session');
            $table->timestamp('charging_start')->nullable();
            $table->timestamp('charging_end')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Charging duration in minutes');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charging_requests');
    }
};
