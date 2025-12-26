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
        // Create RCA table
        Schema::create('root_cause_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('rca_number')->unique(); // RCA-YYYYMM-XXX
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            
            // RCA Details
            $table->text('problem_statement'); // Clear problem description
            $table->text('immediate_cause')->nullable(); // What directly caused the issue
            $table->text('root_cause'); // The underlying root cause
            $table->string('root_cause_category')->nullable(); // Machine, Method, Man, Material, Environment
            
            // Analysis Method
            $table->enum('analysis_method', ['5_whys', 'fishbone', 'fault_tree', 'other'])->default('5_whys');
            $table->json('five_whys')->nullable(); // [{why: "Why 1", answer: "Answer 1"}, ...]
            $table->json('fishbone_data')->nullable(); // {man: [], machine: [], method: [], material: [], environment: []}
            
            // Actions
            $table->text('corrective_actions'); // Immediate fixes
            $table->text('preventive_actions')->nullable(); // Long-term prevention
            $table->date('action_deadline')->nullable();
            $table->string('action_responsible_gpid')->nullable();
            $table->foreign('action_responsible_gpid')->references('gpid')->on('users')->nullOnDelete();
            
            // Status & Workflow
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'closed'])->default('draft');
            $table->string('created_by_gpid');
            $table->foreign('created_by_gpid')->references('gpid')->on('users');
            $table->string('reviewed_by_gpid')->nullable();
            $table->foreign('reviewed_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->string('approved_by_gpid')->nullable();
            $table->foreign('approved_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // AI Integration
            $table->json('ai_suggestions')->nullable(); // Suggestions from AIAnalyticsService
            $table->boolean('ai_assisted')->default(false);
            
            // Effectiveness tracking
            $table->boolean('recurrence_check')->nullable(); // Did issue recur after RCA?
            $table->date('recurrence_check_date')->nullable();
            $table->text('effectiveness_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('created_by_gpid');
            $table->index('action_deadline');
        });

        // Add RCA fields to work_orders table
        Schema::table('work_orders', function (Blueprint $table) {
            $table->boolean('rca_required')->default(false)->after('mttr');
            $table->enum('rca_status', ['not_required', 'pending', 'in_progress', 'completed'])->default('not_required')->after('rca_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['rca_required', 'rca_status']);
        });
        
        Schema::dropIfExists('root_cause_analyses');
    }
};
