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
        Schema::create('barcode_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique(); // UUID
            $table->string('equipment_type')->default('all'); // One barcode for all equipment
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('token');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_tokens');
    }
};
