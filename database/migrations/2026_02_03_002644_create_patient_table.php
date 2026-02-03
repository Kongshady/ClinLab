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
        Schema::create('patient', function (Blueprint $table) {
            $table->integer('patient_id', true, true)->length(10);
            $table->enum('patient_type', ['Internal', 'External'])->default('External');
            $table->string('firstname', 50);
            $table->string('middlename', 50)->nullable();
            $table->string('lastname', 50);
            $table->date('birthdate');
            $table->string('gender', 10);
            $table->string('contact_number', 20)->nullable();
            $table->string('address', 200)->nullable();
            $table->integer('status_code')->length(10)->nullable()->default(1);
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->dateTime('datetime_updated')->nullable()->useCurrentOnUpdate();
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('patient_id');
            $table->index('is_deleted', 'idx_patient_is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient');
    }
};
