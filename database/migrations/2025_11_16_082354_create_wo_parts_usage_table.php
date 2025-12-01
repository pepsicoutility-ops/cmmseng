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
        Schema::create('wo_parts_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->decimal('cost', 15, 2)->default(0);
            $table->enum('status', ['available', 'backorder'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('work_order_id');
            $table->index('part_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wo_parts_usage');
    }
};
