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
        Schema::create('item', function (Blueprint $table) {
            $table->integer('item_id', true, true)->length(10);
            $table->integer('section_id')->length(10);
            $table->integer('item_type_id')->length(10);
            $table->string('label', 20);
            $table->integer('status_code')->length(10);
            $table->string('unit', 20)->nullable()->default('pcs');
            $table->integer('reorder_level')->length(10)->nullable()->default(10);
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('item_id');
            $table->index('section_id', 'idx_item_section');
            $table->index('item_type_id', 'idx_item_type');
            $table->index('status_code', 'idx_item_status');
            $table->index('is_deleted', 'idx_item_is_deleted');
            
            $table->foreign('item_type_id', 'fk_item_itemtype')
                  ->references('item_type_id')->on('item_type');
            $table->foreign('section_id', 'fk_item_section')
                  ->references('section_id')->on('section');
            $table->foreign('status_code', 'fk_item_status')
                  ->references('status_code')->on('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item');
    }
};
