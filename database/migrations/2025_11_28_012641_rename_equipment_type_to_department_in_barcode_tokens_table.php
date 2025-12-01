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
        Schema::table('barcode_tokens', function (Blueprint $table) {
            $table->renameColumn('equipment_type', 'department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barcode_tokens', function (Blueprint $table) {
            $table->renameColumn('department', 'equipment_type');
        });
    }
};
