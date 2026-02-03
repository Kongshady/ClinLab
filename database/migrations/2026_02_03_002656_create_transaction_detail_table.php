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
        Schema::create('transaction_detail', function (Blueprint $table) {
            $table->integer('transaction_detail_id', true, true)->length(10);
            $table->integer('transaction_id')->length(10);
            $table->integer('test_id')->length(10);
            
            $table->primary('transaction_detail_id');
            $table->index('transaction_id', 'idx_txd_txn');
            $table->index('test_id', 'idx_txd_test');
            
            $table->foreign('transaction_id', 'fk_txd_txn')
                  ->references('transaction_id')->on('transaction')
                  ->onDelete('cascade');
            $table->foreign('test_id', 'fk_txd_test')
                  ->references('test_id')->on('test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_detail');
    }
};
