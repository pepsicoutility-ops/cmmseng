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
        Schema::create('pm_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PM-202511-001
            $table->string('title');
            $table->text('description')->nullable();
            
            // Equipment hierarchy
            $table->foreignId('area_id')->nullable()->constrained();
            $table->foreignId('sub_area_id')->nullable()->constrained();
            $table->foreignId('asset_id')->nullable()->constrained();
            $table->foreignId('sub_asset_id')->nullable()->constrained();
            
            // Schedule configuration
            $table->enum('schedule_type', ['weekly', 'running_hours', 'cycle'])->default('weekly');
            $table->integer('frequency')->default(1); // Every 1 week, or every 100 hours
            $table->enum('week_day', [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
            ])->nullable();
            $table->integer('estimated_duration')->default(60); // in minutes
            
            // Assignment (KEY FEATURE: Personalized PM)
            $table->string('assigned_to_gpid')->nullable(); // Technician GPID
            $table->foreign('assigned_to_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->string('assigned_by_gpid')->nullable(); // Asisten Manager GPID
            $table->foreign('assigned_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->enum('department', ['utility', 'electric', 'mechanic'])->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Critical indexes for personalized query
            $table->index('assigned_to_gpid'); // For technician view
            $table->index(['department', 'is_active']); // For asisten_manager view
            $table->index(['status', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_schedules');
    }
};
