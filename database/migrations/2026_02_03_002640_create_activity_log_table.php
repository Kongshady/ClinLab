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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->integer('activity_log_id', true, true)->length(10);
            $table->integer('employee_id')->length(10);
            $table->dateTime('datetime_added');
            $table->string('description', 70);
            $table->integer('status_code')->length(10);
            
            $table->index('employee_id', 'idx_log_employee');
            $table->index('status_code', 'idx_log_status');
            
            $table->foreign('employee_id', 'fk_log_employee')->references('employee_id')->on('employee');
            $table->foreign('status_code', 'fk_log_status')->references('status_code')->on('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
