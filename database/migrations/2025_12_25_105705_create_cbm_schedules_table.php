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
        Schema::create('cbm_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_no')->unique(); // CBM-YYYYMM-XXX
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->enum('checklist_type', [
                'compressor1', 'compressor2', 
                'chiller1', 'chiller2', 
                'ahu'
            ]);
            $table->enum('frequency', ['per_shift', 'daily', 'weekly', 'monthly']);
            $table->integer('shifts_per_day')->default(3); // Typically 3 shifts
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['checklist_type', 'is_active']);
            $table->index(['area_id', 'asset_id']);
        });

        // Track CBM executions vs schedule
        Schema::create('cbm_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbm_schedule_id')->constrained('cbm_schedules')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->integer('scheduled_shift'); // 1, 2, or 3
            $table->bigInteger('checklist_id')->nullable(); // ID from respective checklist table
            $table->boolean('is_executed')->default(false);
            $table->datetime('executed_at')->nullable();
            $table->string('executed_by_gpid')->nullable();
            $table->boolean('is_on_time')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['cbm_schedule_id', 'scheduled_date', 'scheduled_shift'], 'cbm_exec_unique');
            $table->index(['scheduled_date', 'is_executed']);
            
            $table->foreign('executed_by_gpid')
                ->references('gpid')
                ->on('users')
                ->nullOnDelete();
        });

        // Track CBM compliance per period
        Schema::create('cbm_compliances', function (Blueprint $table) {
            $table->id();
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->enum('checklist_type', [
                'compressor1', 'compressor2', 
                'chiller1', 'chiller2', 
                'ahu', 'all'
            ])->default('all');
            $table->integer('scheduled_count')->default(0);
            $table->integer('executed_count')->default(0);
            $table->integer('on_time_count')->default(0);
            $table->integer('late_count')->default(0);
            $table->integer('missed_count')->default(0);
            $table->decimal('compliance_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['period_type', 'period_start', 'area_id', 'checklist_type'], 'cbm_comp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbm_compliances');
        Schema::dropIfExists('cbm_executions');
        Schema::dropIfExists('cbm_schedules');
    }
};
