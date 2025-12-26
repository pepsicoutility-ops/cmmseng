<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Performance indexes based on query analysis.
     * 
     * These indexes optimize:
     * 1. work_orders.created_at - for defaultSort('created_at', 'desc') and date filters
     * 2. inventories quantity/min_stock - for low stock alerts filtering
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->index('created_at', 'idx_work_orders_created_at');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->index(['quantity', 'min_stock'], 'idx_inventories_stock_levels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('idx_work_orders_created_at');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('idx_inventories_stock_levels');
        });
    }
};
