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
        Schema::create('maintenance_history', function (Blueprint $table) {
            $table->integer('history_id', true, true)->length(10);
            $table->integer('equipment_id')->length(10);
            $table->date('maintenance_date');
            $table->integer('performed_by')->length(10)->nullable();
            $table->enum('maintenance_type', ['preventive', 'corrective', 'emergency']);
            $table->text('notes')->nullable();
            $table->string('attachments', 255)->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            
            $table->primary('history_id');
            $table->index('equipment_id');
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_history');
    }
};
