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
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained();
            $table->enum('alert_type', ['low_stock', 'out_of_stock']);
            $table->dateTime('triggered_at');
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->string('resolved_by_gpid')->nullable();
            $table->foreign('resolved_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['part_id', 'is_resolved']);
            $table->index('alert_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
