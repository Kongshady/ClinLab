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
        Schema::create('maintenance_schedule', function (Blueprint $table) {
            $table->integer('schedule_id', true, true)->length(10);
            $table->integer('equipment_id')->length(10);
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'semi-annual', 'annual']);
            $table->date('next_due_date');
            $table->integer('responsible_employee_id')->length(10)->nullable();
            $table->integer('responsible_section_id')->length(10)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('schedule_id');
            $table->index('equipment_id');
            $table->index('responsible_employee_id');
            $table->index('responsible_section_id');
            $table->index('next_due_date', 'idx_maintenance_next_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedule');
    }
};
