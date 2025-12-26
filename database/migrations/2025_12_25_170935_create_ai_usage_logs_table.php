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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('model')->default('gpt-4o-mini');
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0); // Cost in USD
            $table->string('request_type')->default('chat'); // chat, tool_call, export, etc.
            $table->json('metadata')->nullable();
            $table->date('usage_date'); // For daily aggregation
            $table->timestamps();

            // Index for fast daily lookup
            $table->index(['user_id', 'usage_date']);
        });

        // Add daily_token_limit to users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('daily_ai_token_limit')->default(100000)->after('password'); // 100k tokens/day default
            $table->boolean('ai_enabled')->default(true)->after('daily_ai_token_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_ai_token_limit', 'ai_enabled']);
        });
    }
};
