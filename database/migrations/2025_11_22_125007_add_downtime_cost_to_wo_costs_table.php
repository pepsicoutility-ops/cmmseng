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
        Schema::table('wo_costs', function (Blueprint $table) {
            $table->decimal('downtime_cost', 15, 2)->default(0)->after('parts_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wo_costs', function (Blueprint $table) {
            $table->dropColumn('downtime_cost');
        });
    }
};
