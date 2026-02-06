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
        Schema::create('employee', function (Blueprint $table) {
            $table->integer('employee_id', true, true)->length(10);
            $table->string('firstname', 20);
            $table->string('middlename', 20)->nullable();
            $table->string('lastname', 20);
            $table->string('username', 20)->unique('uq_employee_username');
            $table->string('password', 100);
            $table->string('position', 20)->nullable();
            $table->integer('role_id')->length(10)->nullable();
            $table->string('password_hash', 255)->nullable();
            $table->string('role', 20)->default('Staff');
            $table->integer('status_code')->length(10);
            $table->boolean('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->timestamp('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->index('status_code', 'idx_employee_status');
            $table->index('role_id', 'idx_role_id');
            $table->index('is_deleted', 'idx_employee_is_deleted');
            
            $table->foreign('status_code', 'fk_employee_status')->references('status_code')->on('status_code');
            $table->foreign('role_id', 'fk_employee_role')->references('role_id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};
