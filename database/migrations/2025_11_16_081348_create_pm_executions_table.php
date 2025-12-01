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
        Schema::create('pm_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('executed_by_gpid');
            $table->foreign('executed_by_gpid')->references('gpid')->on('users');
            
            // Schedule vs Actual
            $table->date('scheduled_date');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->integer('duration')->nullable(); // in minutes, calculated
            
            // Checklist data (JSON)
            $table->json('checklist_data')->nullable(); // {"item_id": "value", ...}
            $table->text('notes')->nullable();
            $table->json('photos')->nullable(); // ["path/to/photo1.jpg", ...]
            
            // Status & Compliance
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->enum('compliance_status', ['on_time', 'late'])->nullable();
            $table->boolean('is_on_time')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('executed_by_gpid');
            $table->index(['pm_schedule_id', 'status']);
            $table->index(['scheduled_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_executions');
    }
};
