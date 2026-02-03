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
        Schema::create('physician', function (Blueprint $table) {
            $table->integer('physician_id', true, true)->length(10);
            $table->string('physician_name', 100);
            $table->string('specialization', 100)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->integer('status_code')->length(10)->nullable()->default(1);
            $table->dateTime('datetime_added')->nullable()->useCurrent();
            $table->tinyInteger('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->dateTime('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->primary('physician_id');
            $table->index('is_deleted', 'idx_physician_is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physician');
    }
};
