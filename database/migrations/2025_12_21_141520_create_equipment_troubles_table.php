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
        Schema::create('equipment_troubles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('sub_assets')->cascadeOnDelete();
            $table->string('title');
            $table->text('issue_description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'investigating', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reported_at');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->string('downtime_minutes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('priority');
            $table->index('reported_at');
            $table->index(['equipment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_troubles');
    }
};
