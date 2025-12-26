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
        Schema::create('wo_improvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->string('improved_by_gpid');
            $table->enum('improvement_type', [
                'process_optimization',
                'spare_part_standardization',
                'procedure_update',
                'training_provided'
            ]);
            $table->text('description');
            $table->integer('time_saved_minutes')->nullable();
            $table->decimal('cost_saved', 10, 2)->nullable();
            $table->boolean('recurrence_prevented')->default(false);
            $table->timestamps();

            $table->foreign('improved_by_gpid')->references('gpid')->on('users')->onDelete('cascade');

            $table->index('work_order_id');
            $table->index('improved_by_gpid');
            $table->index('improvement_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wo_improvements');
    }
};
