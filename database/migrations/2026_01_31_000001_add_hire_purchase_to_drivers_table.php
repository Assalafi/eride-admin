<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_hire_purchase')->default(false)->after('phone_number');
            $table->enum('hire_purchase_status', ['active', 'completed', 'defaulted', 'terminated'])->nullable()->after('is_hire_purchase');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['is_hire_purchase', 'hire_purchase_status']);
        });
    }
};
