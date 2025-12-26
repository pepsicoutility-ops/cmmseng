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
        Schema::create('area_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->string('owner_gpid');
            $table->date('assigned_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('owner_gpid')->references('gpid')->on('users')->onDelete('cascade');

            $table->unique(['area_id', 'owner_gpid', 'is_active'], 'unique_area_owner');
            $table->index('owner_gpid');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_owners');
    }
};
