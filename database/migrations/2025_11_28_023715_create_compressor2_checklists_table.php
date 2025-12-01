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
        Schema::create('compressor2_checklists', function (Blueprint $table) {
            $table->id();
            $table->enum('shift', ['1', '2', '3'])->comment('Work shift');
            $table->string('gpid')->nullable()->comment('Employee GPID');
            $table->string('name')->nullable()->comment('Employee name (auto-populated)');
            
            // Measurement columns
            $table->decimal('tot_run_hours', 10, 2)->nullable()->comment('Total running hours');
            $table->decimal('bearing_oil_temperature', 8, 2)->nullable()->comment('Bearing oil temperature (°C)');
            $table->decimal('bearing_oil_pressure', 8, 2)->nullable()->comment('Bearing oil pressure (bar)');
            $table->decimal('discharge_pressure', 8, 2)->nullable()->comment('Discharge pressure (bar)');
            $table->decimal('discharge_temperature', 8, 2)->nullable()->comment('Discharge temperature (°C)');
            $table->decimal('cws_temperature', 8, 2)->nullable()->comment('Cooling water supply temperature (°C)');
            $table->decimal('cwr_temperature', 8, 2)->nullable()->comment('Cooling water return temperature (°C)');
            $table->decimal('cws_pressure', 8, 2)->nullable()->comment('Cooling water supply pressure (bar)');
            $table->decimal('cwr_pressure', 8, 2)->nullable()->comment('Cooling water return pressure (bar)');
            $table->decimal('refrigerant_pressure', 8, 2)->nullable()->comment('Refrigerant pressure (bar)');
            $table->decimal('dew_point', 8, 2)->nullable()->comment('Dew point (°C)');
            
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->timestamps();
            
            // Indexes
            $table->index('shift');
            $table->index('gpid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compressor2_checklists');
    }
};
