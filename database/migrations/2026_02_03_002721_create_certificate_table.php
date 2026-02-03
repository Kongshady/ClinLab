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
        Schema::create('certificate', function (Blueprint $table) {
            $table->integer('certificate_id', true, true)->length(10);
            $table->string('certificate_number', 50);
            $table->integer('template_id')->length(10);
            $table->enum('certificate_type', ['lab_result', 'calibration', 'compliance', 'safety', 'other']);
            $table->integer('linked_record_id')->length(10)->nullable();
            $table->string('linked_table', 50)->nullable()->comment('lab_result, calibration_record, etc');
            $table->integer('patient_id')->length(10)->nullable();
            $table->integer('equipment_id')->length(10)->nullable();
            $table->date('issue_date');
            $table->integer('issued_by')->length(10)->nullable();
            $table->integer('verified_by')->length(10)->nullable();
            $table->enum('status', ['draft', 'issued', 'revoked'])->default('draft');
            $table->text('certificate_data')->nullable()->comment('JSON data for certificate fields');
            $table->string('pdf_path', 255)->nullable();
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->dateTime('datetime_modified')->nullable()->useCurrentOnUpdate();
            
            $table->primary('certificate_id');
            $table->unique('certificate_number');
            $table->index('template_id');
            $table->index('patient_id');
            $table->index('equipment_id');
            $table->index('issued_by');
            $table->index('certificate_number', 'idx_certificate_number');
            $table->index('status', 'idx_certificate_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate');
    }
};
