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
        Schema::table('kaizens', function (Blueprint $table) {
            // Change from DECIMAL(10,2) to DECIMAL(15,2) to support larger values
            // Max: 9,999,999,999,999.99 (approximately 10 trillion)
            $table->decimal('cost_saved', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kaizens', function (Blueprint $table) {
            $table->decimal('cost_saved', 10, 2)->nullable()->change();
        });
    }
};
