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
        Schema::create('cbm_parameter_thresholds', function (Blueprint $table) {
            $table->id();
            $table->enum('checklist_type', [
                'compressor1', 'compressor2', 
                'chiller1', 'chiller2', 
                'ahu'
            ]);
            $table->string('parameter_name'); // e.g., 'bearing_oil_temperature', 'discharge_pressure'
            $table->string('parameter_label'); // Human readable: 'Bearing Oil Temperature'
            $table->decimal('min_value', 10, 2)->nullable(); // Minimum acceptable value
            $table->decimal('max_value', 10, 2)->nullable(); // Maximum acceptable value
            $table->decimal('warning_min', 10, 2)->nullable(); // Warning threshold (yellow)
            $table->decimal('warning_max', 10, 2)->nullable(); // Warning threshold (yellow)
            $table->string('unit')->nullable(); // °C, PSI, Bar, etc.
            $table->boolean('is_critical')->default(false); // Critical parameter
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['checklist_type', 'parameter_name']);
            $table->index(['checklist_type', 'is_active']);
        });

        // Track parameter alerts when thresholds exceeded
        Schema::create('cbm_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_no')->unique(); // CBMA-YYYYMM-XXX
            $table->foreignId('threshold_id')->constrained('cbm_parameter_thresholds')->cascadeOnDelete();
            $table->bigInteger('checklist_id'); // ID from respective checklist table
            $table->enum('checklist_type', [
                'compressor1', 'compressor2', 
                'chiller1', 'chiller2', 
                'ahu'
            ]);
            $table->string('parameter_name');
            $table->decimal('recorded_value', 10, 2);
            $table->decimal('threshold_value', 10, 2); // The threshold that was exceeded
            $table->enum('alert_type', ['below_min', 'above_max', 'warning_low', 'warning_high']);
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning');
            $table->enum('status', ['open', 'acknowledged', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->string('acknowledged_by_gpid')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->string('resolved_by_gpid')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['status', 'severity']);
            $table->index(['checklist_type', 'created_at']);
            
            $table->foreign('acknowledged_by_gpid')
                ->references('gpid')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('resolved_by_gpid')
                ->references('gpid')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbm_alerts');
        Schema::dropIfExists('cbm_parameter_thresholds');
    }
};
