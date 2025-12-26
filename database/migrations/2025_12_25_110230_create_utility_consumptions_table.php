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
        Schema::create('utility_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('record_no')->unique(); // UTL-YYYYMM-XXX
            $table->date('consumption_date');
            $table->integer('shift')->nullable(); // 1, 2, 3 or null for daily total
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            
            // Meter readings
            $table->decimal('water_meter_start', 15, 2)->nullable(); // m³ or liters
            $table->decimal('water_meter_end', 15, 2)->nullable();
            $table->decimal('water_consumption', 15, 2)->default(0); // Calculated: end - start (liters)
            
            $table->decimal('electricity_meter_start', 15, 2)->nullable(); // kWh
            $table->decimal('electricity_meter_end', 15, 2)->nullable();
            $table->decimal('electricity_consumption', 15, 2)->default(0); // Calculated: end - start (kWh)
            
            $table->decimal('gas_meter_start', 15, 2)->nullable(); // m³ or kWh
            $table->decimal('gas_meter_end', 15, 2)->nullable();
            $table->decimal('gas_consumption', 15, 2)->default(0); // Calculated: end - start (kWh)
            
            // Costs (optional, can be calculated from rates)
            $table->decimal('water_cost', 15, 2)->nullable();
            $table->decimal('electricity_cost', 15, 2)->nullable();
            $table->decimal('gas_cost', 15, 2)->nullable();
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'verified', 'approved'])->default('draft');
            $table->string('recorded_by_gpid');
            $table->string('verified_by_gpid')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->string('approved_by_gpid')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['consumption_date', 'shift', 'area_id'], 'util_unique');
            $table->index(['consumption_date', 'area_id']);
            $table->index(['status', 'consumption_date']);
            
            $table->foreign('recorded_by_gpid')
                ->references('gpid')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('verified_by_gpid')
                ->references('gpid')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('approved_by_gpid')
                ->references('gpid')
                ->on('users')
                ->nullOnDelete();
        });

        // Utility rates for cost calculation
        Schema::create('utility_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('utility_type', ['water', 'electricity', 'gas']);
            $table->decimal('rate_per_unit', 15, 4); // Cost per liter/kWh
            $table->string('unit'); // liter, kWh, m³
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['utility_type', 'is_active']);
        });

        // Utility cost targets for comparison
        Schema::create('utility_targets', function (Blueprint $table) {
            $table->id();
            $table->enum('utility_type', ['water', 'electricity', 'gas']);
            $table->decimal('target_per_kg', 10, 4); // Target consumption per kg produced
            $table->string('unit'); // L/kg, kWh/kg
            $table->enum('comparison_operator', ['<=', '>=', '<', '>'])->default('<=');
            $table->integer('year');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['utility_type', 'year', 'area_id'], 'util_target_unique');
        });

        // Daily/weekly/monthly utility per kg calculations
        Schema::create('utility_per_kg_reports', function (Blueprint $table) {
            $table->id();
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            
            // Production
            $table->decimal('total_production_kg', 15, 2)->default(0);
            
            // Consumption
            $table->decimal('total_water_liters', 15, 2)->default(0);
            $table->decimal('total_electricity_kwh', 15, 2)->default(0);
            $table->decimal('total_gas_kwh', 15, 2)->default(0);
            
            // Per kg metrics
            $table->decimal('water_per_kg', 10, 4)->default(0); // L/kg
            $table->decimal('electricity_per_kg', 10, 4)->default(0); // kWh/kg
            $table->decimal('gas_per_kg', 10, 4)->default(0); // kWh/kg
            
            // Target comparison
            $table->decimal('water_target', 10, 4)->nullable();
            $table->decimal('electricity_target', 10, 4)->nullable();
            $table->decimal('gas_target', 10, 4)->nullable();
            
            // Status (vs target)
            $table->enum('water_status', ['on_target', 'warning', 'exceeded'])->nullable();
            $table->enum('electricity_status', ['on_target', 'warning', 'exceeded'])->nullable();
            $table->enum('gas_status', ['on_target', 'warning', 'exceeded'])->nullable();
            
            $table->timestamps();
            
            $table->unique(['period_type', 'period_start', 'area_id'], 'util_pkg_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_per_kg_reports');
        Schema::dropIfExists('utility_targets');
        Schema::dropIfExists('utility_rates');
        Schema::dropIfExists('utility_consumptions');
    }
};
