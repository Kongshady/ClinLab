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
        Schema::create('blood_chemistry', function (Blueprint $table) {
            $table->integer('blood_chemistry_id', true, true)->length(10);
            $table->integer('employee_id')->length(10);
            $table->integer('transaction_id')->length(10);
            $table->integer('lab_number')->length(10);
            $table->float('urates');
            $table->float('glucose');
            $table->dateTime('datetime_added');
            
            $table->primary('blood_chemistry_id');
            $table->index('employee_id', 'idx_bc_employee');
            $table->index('transaction_id', 'idx_bc_txn');
            
            $table->foreign('employee_id', 'fk_bc_employee')
                  ->references('employee_id')->on('employee');
            $table->foreign('transaction_id', 'fk_bc_txn')
                  ->references('transaction_id')->on('transaction')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_chemistry');
    }
};
