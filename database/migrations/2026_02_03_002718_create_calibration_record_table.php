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
        Schema::create('calibration_record', function (Blueprint $table) {
            $table->integer('record_id', true, true)->length(10);
            $table->integer('procedure_id')->length(10);
            $table->integer('equipment_id')->length(10);
            $table->date('calibration_date');
            $table->integer('performed_by')->length(10)->nullable();
            $table->enum('result_status', ['pass', 'fail', 'conditional']);
            $table->text('notes')->nullable();
            $table->string('attachments', 255)->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('record_id');
            $table->index('procedure_id');
            $table->index('equipment_id');
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_record');
    }
};
