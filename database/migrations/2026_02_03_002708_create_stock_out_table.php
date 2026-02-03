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
        Schema::create('stock_out', function (Blueprint $table) {
            $table->integer('stock_out_id', true, true)->length(10);
            $table->integer('item_id')->length(10);
            $table->integer('quantity')->length(5);
            $table->integer('performed_by')->length(10)->nullable();
            $table->string('reference_number', 50)->nullable()->comment('Requisition/Request number');
            $table->text('remarks')->nullable();
            $table->dateTime('datetime_added');
            
            $table->primary('stock_out_id');
            $table->index('item_id', 'idx_stockout_item');
            $table->index('performed_by', 'fk_stock_out_employee');
            $table->index('datetime_added', 'idx_stock_out_date');
            
            $table->foreign('performed_by', 'fk_stock_out_employee')
                  ->references('employee_id')->on('employee')
                  ->onDelete('set null');
            $table->foreign('item_id', 'fk_stockout_item')
                  ->references('item_id')->on('item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out');
    }
};
