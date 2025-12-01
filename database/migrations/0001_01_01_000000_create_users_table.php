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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('gpid')->unique(); // SA001, MGR001, TCM001
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('role', [
                'super_admin',
                'manager',
                'asisten_manager',
                'technician',
                'tech_store',
                'operator'
            ])->default('operator');
            
            // Department only for asisten_manager and technician
            $table->enum('department', [
                'utility',
                'electric',
                'mechanic'
            ])->nullable();
            
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('gpid');
            $table->index(['role', 'department']);
            $table->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['gpid']);
            $table->dropIndex(['role', 'department']);
            $table->dropIndex(['is_active']);
            
            $table->dropColumn([
                'gpid',
                'role',
                'department',
                'phone',
                'is_active'
            ]);
        });
    }
};
