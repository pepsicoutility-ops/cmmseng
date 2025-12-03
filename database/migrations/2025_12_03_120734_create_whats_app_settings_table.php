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
        Schema::create('whats_app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('waha_api_url')->nullable();
            $table->string('waha_api_token')->nullable();
            $table->string('waha_session')->default('default');
            $table->string('waha_group_id')->nullable();
            $table->boolean('waha_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_settings');
    }
};
