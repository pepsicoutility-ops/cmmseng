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
        Schema::create('pm_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->enum('item_type', ['checkbox', 'input', 'photo', 'dropdown'])->default('checkbox');
            $table->json('options')->nullable(); // For dropdown: ["OK", "NG", "NA"]
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            
            $table->index(['pm_schedule_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_checklist_items');
    }
};
