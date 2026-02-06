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
        Schema::create('transaction', function (Blueprint $table) {
            $table->integer('transaction_id', true, true)->length(10);
            $table->integer('client_id')->length(10);
            $table->integer('or_number')->length(10);
            $table->dateTime('datetime_added');
            $table->integer('status_code')->length(10);
            
            $table->primary('transaction_id');
            $table->index('client_id', 'idx_txn_client');
            $table->index('status_code', 'idx_txn_status');
            
            $table->foreign('client_id', 'fk_txn_patient')
                  ->references('patient_id')->on('patient');
            $table->foreign('status_code', 'fk_txn_status')
                  ->references('status_code')->on('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
