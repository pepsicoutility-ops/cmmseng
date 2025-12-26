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
        Schema::create('abnormalities', function (Blueprint $table) {
            $table->id();
            $table->string('abnormality_no')->unique(); // Auto-generated: ABN-YYYYMM-XXX
            $table->string('title');
            $table->text('description');
            $table->string('location'); // Where found
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('reported_by'); // GPID
            $table->date('found_date');
            $table->string('photo')->nullable(); // Evidence photo
            
            // Severity & Deadline
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            // Critical: 24h, High: 3 days, Medium: 7 days, Low: 14 days
            $table->date('deadline');
            
            // Assignment
            $table->string('assigned_to')->nullable(); // GPID
            $table->timestamp('assigned_at')->nullable();
            
            // Status workflow: open -> assigned -> in_progress -> fixed -> verified -> closed
            $table->enum('status', ['open', 'assigned', 'in_progress', 'fixed', 'verified', 'closed'])->default('open');
            
            // Fix details
            $table->text('fix_description')->nullable();
            $table->string('fix_photo')->nullable();
            $table->timestamp('fixed_at')->nullable();
            $table->string('fixed_by')->nullable(); // GPID
            
            // Verification
            $table->string('verified_by')->nullable(); // GPID (usually supervisor)
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Link to Work Order if created
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('reported_by');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('severity');
            $table->index('found_date');
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abnormalities');
    }
};
