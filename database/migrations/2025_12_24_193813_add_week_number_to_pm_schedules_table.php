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
        Schema::table('pm_schedules', function (Blueprint $table) {
            // Add week_number field (1-52) untuk schedule tahunan
            $table->integer('week_number')->nullable()->after('week_day')
                ->comment('Week number in year (1-52) when this PM should be executed');
            
            // Add index untuk query performance
            $table->index('week_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->dropIndex(['week_number']);
            $table->dropColumn('week_number');
        });
    }
};
