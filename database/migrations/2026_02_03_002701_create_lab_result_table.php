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
        Schema::create('lab_result', function (Blueprint $table) {
            $table->integer('lab_result_id', true, true)->length(10);
            $table->integer('order_test_id')->length(10)->nullable();
            $table->integer('lab_test_order_id')->length(10);
            $table->integer('patient_id')->length(10);
            $table->integer('test_id')->length(10);
            $table->dateTime('result_date')->nullable()->useCurrent();
            $table->text('findings')->nullable();
            $table->string('normal_range', 100)->nullable();
            $table->string('result_value', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->integer('performed_by')->length(10)->nullable();
            $table->integer('verified_by')->length(10)->nullable();
            $table->enum('status', ['draft', 'final', 'revised'])->default('draft');
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->dateTime('datetime_modified')->nullable()->useCurrentOnUpdate();
            
            $table->primary('lab_result_id');
            $table->index('lab_test_order_id');
            $table->index('patient_id');
            $table->index('test_id');
            $table->index('performed_by');
            $table->index('verified_by');
            $table->index('order_test_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_result');
    }
};
