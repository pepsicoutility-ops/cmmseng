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
        Schema::create('kaizens', function (Blueprint $table) {
            $table->id();
            $table->string('submitted_by_gpid');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['RECON', 'DT_REDUCTION', 'SAFETY_QUALITY']);
            $table->integer('score'); // RECON=5, DT=3, Safety=1
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'implemented'])->default('submitted');
            $table->text('before_situation')->nullable();
            $table->text('after_situation')->nullable();
            $table->decimal('cost_saved', 10, 2)->nullable();
            $table->date('implementation_date')->nullable();
            $table->json('attachments')->nullable(); // [{name, path}]
            $table->string('reviewed_by_gpid')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by_gpid')->references('gpid')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by_gpid')->references('gpid')->on('users')->onDelete('set null');

            $table->index('submitted_by_gpid');
            $table->index('category');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kaizens');
    }
};
