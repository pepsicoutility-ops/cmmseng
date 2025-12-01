<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wo_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->enum('action', [
                'review',
                'approve',
                'start',
                'hold',
                'continue',
                'complete',
                'close'
            ]);
            $table->string('performed_by_gpid');
            $table->foreign('performed_by_gpid')->references('gpid')->on('users');
            $table->dateTime('timestamp')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('notes')->nullable();
            
            // For downtime calculation
            $table->dateTime('downtime_start')->nullable();
            $table->dateTime('downtime_end')->nullable();
            $table->integer('downtime_duration')->nullable(); // in minutes
            
            $table->timestamps();
            
            $table->index(['work_order_id', 'action']);
            $table->index('performed_by_gpid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wo_processes');
    }
};
