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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number')->unique(); // WO-202511-0001
            
            // Created by operator (via barcode)
            $table->string('created_by_gpid')->nullable();
            $table->foreign('created_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->string('operator_name');
            $table->enum('shift', ['1', '2', '3']);
            
            // Problem details
            $table->enum('problem_type', [
                'abnormality',
                'breakdown',
                'request_consumable',
                'improvement',
                'inspection'
            ]);
            $table->enum('assign_to', ['utility', 'mechanic', 'electric']);
            
            // Equipment hierarchy
            $table->foreignId('area_id')->constrained();
            $table->foreignId('sub_area_id')->constrained();
            $table->foreignId('asset_id')->constrained();
            $table->foreignId('sub_asset_id')->constrained();
            
            $table->text('description');
            $table->json('photos')->nullable(); // ["path/to/photo1.jpg", ...]
            
            // Status & Priority
            $table->enum('status', [
                'submitted',
                'reviewed',
                'approved',
                'in_progress',
                'on_hold',
                'completed',
                'closed'
            ])->default('submitted');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Timestamps
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            
            // Metrics (calculated)
            $table->integer('total_downtime')->nullable(); // in minutes
            $table->integer('mttr')->nullable(); // Mean Time To Repair (in minutes)
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('wo_number');
            $table->index(['assign_to', 'status']);
            $table->index(['status', 'priority']);
            $table->index('created_by_gpid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
