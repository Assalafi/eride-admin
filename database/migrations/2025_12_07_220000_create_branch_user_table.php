<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('Primary branch for this user');
            $table->timestamps();
            
            // Ensure a user can only be assigned to the same branch once
            $table->unique(['user_id', 'branch_id']);
            
            // Ensure each user has only one primary branch
            $table->unique(['user_id', 'is_primary'], 'user_primary_branch_unique')->where('is_primary', true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
