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
        Schema::create('test', function (Blueprint $table) {
            $table->integer('test_id', true, true)->length(10);
            $table->integer('section_id')->length(10);
            $table->string('label', 20);
            $table->decimal('current_price', 10, 2)->default(0.00);
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('test_id');
            $table->index('section_id', 'idx_test_section');
            $table->index('is_deleted', 'idx_test_is_deleted');
            
            $table->foreign('section_id', 'fk_test_section')
                  ->references('section_id')->on('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test');
    }
};
