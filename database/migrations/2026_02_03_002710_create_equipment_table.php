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
        Schema::create('equipment', function (Blueprint $table) {
            $table->integer('equipment_id', true, true)->length(10);
            $table->string('name', 100);
            $table->string('model', 100)->nullable();
            $table->string('serial_no', 100)->nullable();
            $table->integer('section_id')->length(10)->nullable();
            $table->enum('status', ['operational', 'under_maintenance', 'decommissioned'])->default('operational');
            $table->date('purchase_date')->nullable();
            $table->string('supplier', 200)->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('equipment_id');
            $table->index('section_id');
            $table->index('status', 'idx_equipment_status');
            $table->index('is_deleted', 'idx_equipment_is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
