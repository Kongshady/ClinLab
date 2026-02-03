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
        Schema::create('calibration_procedure', function (Blueprint $table) {
            $table->integer('procedure_id', true, true)->length(10);
            $table->integer('equipment_id')->length(10);
            $table->string('procedure_name', 200);
            $table->string('standard_reference', 200)->nullable();
            $table->enum('frequency', ['monthly', 'quarterly', 'semi-annual', 'annual']);
            $table->date('next_due_date');
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('procedure_id');
            $table->index('equipment_id');
            $table->index('next_due_date', 'idx_calibration_next_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_procedure');
    }
};
