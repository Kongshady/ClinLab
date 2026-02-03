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
        Schema::create('fecalysis', function (Blueprint $table) {
            $table->integer('fecalysis_id', true, true)->length(11);
            $table->integer('employee_id')->length(11);
            $table->integer('transaction_id')->length(11);
            $table->integer('lab_number')->length(11);
            $table->dateTime('datetime_added');
            $table->string('color', 15);
            
            $table->primary('fecalysis_id');
            $table->index('employee_id', 'idx_fec_employee');
            $table->index('transaction_id', 'idx_fec_txn');
            
            $table->foreign('employee_id', 'fk_fec_employee')
                  ->references('employee_id')->on('employee');
            $table->foreign('transaction_id', 'fk_fec_txn')
                  ->references('transaction_id')->on('transaction')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fecalysis');
    }
};
