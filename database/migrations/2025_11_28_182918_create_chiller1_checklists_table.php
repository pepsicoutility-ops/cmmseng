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
        Schema::create('chiller1_checklists', function (Blueprint $table) {
            $table->id();
            $table->integer('shift');
            $table->string('gpid')->nullable();
            $table->string('name')->nullable();
            
            // Chiller measurements (29 fields)
            $table->decimal('sat_evap_t', 8, 2)->nullable();
            $table->decimal('sat_dis_t', 8, 2)->nullable();
            $table->decimal('dis_superheat', 8, 2)->nullable();
            $table->decimal('lcl', 8, 2)->nullable();
            $table->decimal('fla', 8, 2)->nullable();
            $table->decimal('ecl', 8, 2)->nullable();
            $table->decimal('lel', 8, 2)->nullable();
            $table->decimal('eel', 8, 2)->nullable();
            $table->decimal('evap_p', 8, 2)->nullable();
            $table->decimal('conds_p', 8, 2)->nullable();
            $table->decimal('oil_p', 8, 2)->nullable();
            $table->decimal('evap_t_diff', 8, 2)->nullable();
            $table->decimal('conds_t_diff', 8, 2)->nullable();
            $table->decimal('reff_levels', 8, 2)->nullable();
            $table->decimal('motor_amps', 8, 2)->nullable();
            $table->decimal('motor_volts', 8, 2)->nullable();
            $table->decimal('heatsink_t', 8, 2)->nullable();
            $table->decimal('run_hours', 8, 2)->nullable();
            $table->decimal('motor_t', 8, 2)->nullable();
            $table->string('comp_oil_level')->nullable();
            $table->decimal('cooler_reff_small_temp_diff', 8, 2)->nullable();
            $table->decimal('cooler_liquid_inlet_pressure', 8, 2)->nullable();
            $table->decimal('cooler_liquid_outlet_pressure', 8, 2)->nullable();
            $table->decimal('cooler_pressure_drop', 8, 2)->nullable();
            $table->decimal('cond_reff_small_temp_diff', 8, 2)->nullable();
            $table->decimal('cond_liquid_inlet_pressure', 8, 2)->nullable();
            $table->decimal('cond_liquid_outlet_pressure', 8, 2)->nullable();
            $table->decimal('cond_pressure_drop', 8, 2)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
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
        Schema::dropIfExists('chiller1_checklists');
    }
};
