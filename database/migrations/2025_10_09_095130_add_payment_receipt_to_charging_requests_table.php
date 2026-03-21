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
        Schema::table('charging_requests', function (Blueprint $table) {
            $table->string('payment_receipt')->nullable()->after('charging_cost')->comment('Receipt of charging payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_requests', function (Blueprint $table) {
            $table->dropColumn('payment_receipt');
        });
    }
};
