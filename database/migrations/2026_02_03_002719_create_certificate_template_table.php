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
        Schema::create('certificate_template', function (Blueprint $table) {
            $table->integer('template_id', true, true)->length(10);
            $table->string('template_name', 100);
            $table->enum('template_type', ['lab_result', 'calibration', 'compliance', 'safety', 'other']);
            $table->text('html_layout');
            $table->string('version', 20)->nullable()->default('1.0');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->dateTime('datetime_modified')->nullable()->useCurrentOnUpdate();
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('template_id');
            $table->index('is_deleted', 'idx_certificate_template_is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_template');
    }
};
