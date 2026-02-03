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
        Schema::create('lab_test_order', function (Blueprint $table) {
            $table->integer('lab_test_order_id', true, true)->length(10);
            $table->integer('patient_id')->length(10);
            $table->integer('physician_id')->length(10)->nullable();
            $table->integer('test_id')->length(10);
            $table->dateTime('order_date')->nullable()->useCurrent();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->string('remarks', 200)->nullable();
            
            $table->primary('lab_test_order_id');
            $table->index('patient_id');
            $table->index('physician_id');
            $table->index('test_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_order');
    }
};
