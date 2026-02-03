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
        Schema::create('order_tests', function (Blueprint $table) {
            $table->integer('order_test_id', true, true)->length(10);
            $table->integer('order_id')->length(10);
            $table->integer('test_id')->length(10);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('assigned_to')->length(10)->nullable()->comment('Employee/technician assigned');
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('order_test_id');
            $table->index('order_id');
            $table->index('test_id');
            $table->index('status', 'idx_order_tests_status');
            $table->index('order_id', 'idx_order_tests_order');
            
            $table->foreign('order_id', 'fk_order_tests_order')
                  ->references('lab_test_order_id')->on('lab_test_order')
                  ->onDelete('cascade');
            $table->foreign('test_id', 'fk_order_tests_test')
                  ->references('test_id')->on('test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tests');
    }
};
