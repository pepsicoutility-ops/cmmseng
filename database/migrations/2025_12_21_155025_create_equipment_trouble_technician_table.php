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
        Schema::create('equipment_trouble_technician', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_trouble_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('equipment_trouble_id')
                ->references('id')
                ->on('equipment_troubles')
                ->cascadeOnDelete();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->primary(['equipment_trouble_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_trouble_technician');
    }
};
