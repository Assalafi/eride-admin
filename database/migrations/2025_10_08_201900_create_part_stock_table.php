<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestamps();
            
            $table->unique(['part_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_stock');
    }
};
