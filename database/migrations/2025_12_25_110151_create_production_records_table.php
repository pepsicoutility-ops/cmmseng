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
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_no')->unique(); // PRD-YYYYMM-XXX
            $table->date('production_date');
            $table->integer('shift')->nullable(); // 1, 2, 3 or null for daily total
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('sub_areas')->nullOnDelete(); // Line
            
            // Production metrics
            $table->decimal('weight_kg', 15, 2)->default(0); // Total production weight in kg
            $table->decimal('good_product_kg', 15, 2)->default(0); // Good/saleable product
            $table->decimal('waste_kg', 15, 2)->default(0); // Waste/reject product
            $table->integer('production_hours', false, true)->default(0); // Actual production hours (minutes)
            $table->integer('downtime_minutes', false, true)->default(0); // Total downtime
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'verified', 'approved'])->default('draft');
            $table->string('recorded_by_gpid');
            $table->string('verified_by_gpid')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->string('approved_by_gpid')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['production_date', 'shift', 'area_id', 'sub_area_id'], 'prod_unique');
            $table->index(['production_date', 'area_id']);
            $table->index(['status', 'production_date']);
            
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_records');
    }
};
