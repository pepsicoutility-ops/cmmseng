<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Workflow:
     * 1. submitted - Technician submits kaizen
     * 2. under_review - AM reviews
     * 3. approved - AM approves
     * 4. in_progress - Technician starts execution
     * 5. completed - Technician completes
     * 6. closed - AM closes
     * 7. rejected - AM rejects (can happen at review stage)
     */
    public function up(): void
    {
        Schema::table('kaizens', function (Blueprint $table) {
            // Add department column for filtering
            $table->string('department')->nullable()->after('submitted_by_gpid');
            
            // Add execution tracking fields
            $table->timestamp('approved_at')->nullable()->after('review_notes');
            $table->timestamp('started_at')->nullable()->after('approved_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('closed_at')->nullable()->after('completed_at');
            $table->string('closed_by_gpid')->nullable()->after('closed_at');
            
            // Add completion notes
            $table->text('completion_notes')->nullable()->after('closed_by_gpid');
            
            // Add index for department
            $table->index('department');
            
            // Add foreign key for closed_by
            $table->foreign('closed_by_gpid')->references('gpid')->on('users')->onDelete('set null');
        });

        // Update enum to include new statuses
        DB::statement("ALTER TABLE kaizens MODIFY COLUMN status ENUM('submitted', 'under_review', 'approved', 'rejected', 'in_progress', 'completed', 'closed') DEFAULT 'submitted'");
        
        // Update existing 'implemented' status to 'closed'
        DB::statement("UPDATE kaizens SET status = 'closed' WHERE status = 'implemented'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status changes
        DB::statement("UPDATE kaizens SET status = 'implemented' WHERE status IN ('in_progress', 'completed', 'closed')");
        DB::statement("ALTER TABLE kaizens MODIFY COLUMN status ENUM('submitted', 'under_review', 'approved', 'rejected', 'implemented') DEFAULT 'submitted'");

        Schema::table('kaizens', function (Blueprint $table) {
            $table->dropForeign(['closed_by_gpid']);
            $table->dropIndex(['department']);
            
            $table->dropColumn([
                'department',
                'approved_at',
                'started_at',
                'completed_at',
                'closed_at',
                'closed_by_gpid',
                'completion_notes',
            ]);
        });
    }
};
