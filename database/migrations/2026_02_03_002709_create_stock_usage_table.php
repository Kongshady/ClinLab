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
        Schema::create('stock_usage', function (Blueprint $table) {
            $table->integer('stock_usage_id', true, true)->length(10);
            $table->integer('item_id')->length(10);
            $table->integer('quantity')->length(5)->default(1);
            $table->integer('employee_id')->length(10);
            $table->string('firstname', 20);
            $table->string('middlename', 20)->nullable();
            $table->string('lastname', 20);
            $table->string('purpose', 30);
            $table->dateTime('datetime_added');
            $table->integer('or_number')->length(10);
            
            $table->primary('stock_usage_id');
            $table->index('item_id', 'idx_usage_item');
            $table->index('employee_id', 'idx_usage_employee');
            $table->index('datetime_added', 'idx_stock_usage_date');
            
            $table->foreign('employee_id', 'fk_usage_employee')
                  ->references('employee_id')->on('employee');
            $table->foreign('item_id', 'fk_usage_item')
                  ->references('item_id')->on('item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_usage');
    }
};
