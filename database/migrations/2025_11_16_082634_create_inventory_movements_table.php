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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained();
            $table->enum('movement_type', ['in', 'out', 'adjustment']);
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            
            // Reference to PM or WO
            $table->string('reference_type')->nullable(); // pm_execution, work_order, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->string('performed_by_gpid');
            $table->foreign('performed_by_gpid')->references('gpid')->on('users');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('part_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('performed_by_gpid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
