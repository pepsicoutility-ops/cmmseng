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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_no')->unique(); // Auto-generated: OPL-YYYYMM-XXX or SOP-YYYYMM-XXX
            $table->enum('type', ['opl', 'sop']); // One Point Lesson or Standard Operating Procedure
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content'); // Rich text content with images
            
            // Categorization
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('category')->nullable(); // e.g., Safety, Quality, Maintenance, etc.
            $table->json('tags')->nullable(); // Searchable tags
            
            // Author & Approval
            $table->string('created_by'); // GPID
            $table->enum('status', ['draft', 'pending_review', 'approved', 'published', 'archived'])->default('draft');
            $table->string('reviewed_by')->nullable(); // GPID (Asisten Manager)
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('approved_by')->nullable(); // GPID (Manager)
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            
            // Version control
            $table->integer('version')->default(1);
            $table->foreignId('parent_id')->nullable()->constrained('documents')->nullOnDelete(); // Previous version
            
            // Attachments
            $table->json('attachments')->nullable(); // Additional files (PDF, images, etc.)
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('created_by');
            $table->index('category');
            $table->fullText(['title', 'description']);
        });
        
        // Document read acknowledgments
        Schema::create('document_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('gpid'); // User who acknowledged
            $table->timestamp('acknowledged_at');
            $table->text('notes')->nullable();
            
            $table->unique(['document_id', 'gpid']);
            $table->index('gpid');
            $table->index('acknowledged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_acknowledgments');
        Schema::dropIfExists('documents');
    }
};
