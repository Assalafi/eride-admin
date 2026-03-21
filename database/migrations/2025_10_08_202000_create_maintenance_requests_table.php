<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('issue_description')->nullable();
            $table->json('issue_photos')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'rejected'
            ])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
