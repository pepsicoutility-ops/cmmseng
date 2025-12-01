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
        Schema::create('pm_parts_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_execution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->decimal('cost', 15, 2)->default(0); // quantity * unit_price
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('pm_execution_id');
            $table->index('part_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_parts_usage');
    }
};
