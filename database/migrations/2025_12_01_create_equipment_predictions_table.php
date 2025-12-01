<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_type'); // chiller1, chiller2, compressor1, compressor2, ahu
            $table->string('checklist_type'); // matches checklist table name
            $table->unsignedBigInteger('checklist_id'); // reference to checklist record
            
            // ONNX Prediction Results
            $table->boolean('is_anomaly')->default(false);
            $table->string('risk_signal')->nullable(); // low, medium, high, critical
            $table->string('raw_label')->nullable(); // model output label
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0-100%
            $table->json('feature_importance')->nullable(); // which features contributed
            
            // OpenAI Insights
            $table->text('root_cause')->nullable();
            $table->text('technical_recommendations')->nullable();
            $table->string('severity_level')->nullable(); // normal, warning, critical
            $table->integer('equipment_priority')->nullable(); // 1-10 scale
            $table->json('ai_metadata')->nullable(); // additional AI response data
            
            $table->timestamp('predicted_at');
            $table->timestamps();
            
            // Indexes
            $table->index('equipment_type');
            $table->index('checklist_id');
            $table->index('is_anomaly');
            $table->index('risk_signal');
            $table->index('predicted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_predictions');
    }
};
