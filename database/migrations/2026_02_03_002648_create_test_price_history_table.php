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
        Schema::create('test_price_history', function (Blueprint $table) {
            $table->integer('price_history_id', true, true)->length(11);
            $table->integer('test_id')->length(10);
            $table->decimal('previous_price', 10, 2)->default(0.00);
            $table->decimal('new_price', 10, 2)->default(0.00);
            $table->integer('updated_by')->length(11)->nullable();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->primary('price_history_id');
            $table->index('test_id');
            $table->index('updated_by');
            
            $table->foreign('test_id', 'fk_price_history_test')
                  ->references('test_id')->on('test')
                  ->onDelete('cascade');
            $table->foreign('updated_by', 'fk_price_history_employee')
                  ->references('employee_id')->on('employee')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_price_history');
    }
};
