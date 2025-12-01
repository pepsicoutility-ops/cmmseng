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
        Schema::create('pm_compliances', function (Blueprint $table) {
             $table->id();
            $table->enum('period', ['week', 'month']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_pm')->default(0);
            $table->integer('completed_pm')->default(0);
            $table->integer('overdue_pm')->default(0);
            $table->decimal('compliance_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->index(['period', 'period_start']);
            $table->unique(['period', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_compliances');
    }
};
