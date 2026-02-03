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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->integer('session_id', true, true)->length(10);
            $table->integer('employee_id')->length(10);
            $table->string('session_token', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('login_time')->nullable()->useCurrent();
            $table->dateTime('last_activity')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->tinyInteger('is_active')->default(1);
            
            $table->primary('session_id');
            $table->index('employee_id');
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
