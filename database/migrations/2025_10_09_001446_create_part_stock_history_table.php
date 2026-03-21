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
        Schema::create('part_stock_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment'])->comment('in=stock added, out=used in maintenance, adjustment=manual correction');
            $table->integer('quantity')->comment('Quantity added or removed');
            $table->integer('quantity_before')->comment('Stock quantity before change');
            $table->integer('quantity_after')->comment('Stock quantity after change');
            $table->string('reference')->nullable()->comment('Reference to related record (e.g., maintenance request ID)');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('User who made the change');
            $table->timestamps();
            
            $table->index(['part_id', 'branch_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_stock_history');
    }
};
