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
        Schema::create('running_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained();
            $table->dateTime('recorded_at');
            $table->decimal('hours', 10, 2)->default(0);
            $table->integer('cycles')->default(0);
            $table->string('recorded_by_gpid')->nullable();
            $table->foreign('recorded_by_gpid')->references('gpid')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['asset_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_hours');
    }
};
