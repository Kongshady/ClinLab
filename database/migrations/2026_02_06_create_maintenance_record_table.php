<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_record', function (Blueprint $table) {
            $table->id('maintenance_id');
            $table->integer('equipment_id')->length(10);
            $table->date('performed_date');
            $table->text('findings')->nullable();
            $table->text('action_taken')->nullable();
            $table->integer('performed_by')->length(10)->nullable();
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['completed', 'pending', 'overdue'])->default('completed');
            $table->dateTime('datetime_added')->nullable();
            $table->dateTime('datetime_updated')->nullable();
            $table->boolean('is_deleted')->default(0);
            $table->dateTime('deleted_at')->nullable();
            $table->integer('deleted_by')->length(10)->nullable();

            $table->foreign('equipment_id')->references('equipment_id')->on('equipment')->onDelete('cascade');
            $table->foreign('performed_by')->references('employee_id')->on('employee')->onDelete('no action');
            $table->foreign('deleted_by')->references('employee_id')->on('employee')->onDelete('no action');
            
            $table->index('equipment_id');
            $table->index('performed_date');
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_record');
    }
};
