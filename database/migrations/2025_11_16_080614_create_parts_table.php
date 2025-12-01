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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique(); // B-123, C-456
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // Electrical, Mechanical, Consumable
            $table->string('unit'); // pcs, kg, liter, meter
            $table->integer('min_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('location')->nullable(); // Warehouse A, Shelf B-3
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('part_number');
            $table->index('category');
            $table->index('current_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
