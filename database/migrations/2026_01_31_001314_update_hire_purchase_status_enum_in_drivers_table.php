<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('hire_purchase_status');
        });
        
        Schema::table('drivers', function (Blueprint $table) {
            $table->enum('hire_purchase_status', ['pending', 'active', 'completed', 'defaulted', 'terminated'])->nullable()->after('is_hire_purchase');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('hire_purchase_status');
        });
        
        Schema::table('drivers', function (Blueprint $table) {
            $table->enum('hire_purchase_status', ['active', 'completed', 'defaulted', 'terminated'])->nullable()->after('is_hire_purchase');
        });
    }
};
