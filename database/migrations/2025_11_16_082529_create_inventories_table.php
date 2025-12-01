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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained();
            
            // Hierarchical location (optional)
            $table->foreignId('area_id')->nullable()->constrained();
            $table->foreignId('sub_area_id')->nullable()->constrained();
            $table->foreignId('asset_id')->nullable()->constrained();
            $table->foreignId('sub_asset_id')->nullable()->constrained();
            
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->string('location')->nullable(); // Physical location
            $table->dateTime('last_restocked_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('part_id');
            $table->index(['part_id', 'quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
