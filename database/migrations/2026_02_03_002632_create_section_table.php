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
        Schema::create('section', function (Blueprint $table) {
            $table->integer('section_id', true, true)->length(10);
            $table->string('label', 50);
            $table->boolean('is_deleted')->default(0)->comment('Soft delete flag: 0=active, 1=deleted');
            $table->timestamp('deleted_at')->nullable()->comment('Timestamp when record was soft deleted');
            $table->integer('deleted_by')->length(10)->nullable()->comment('Employee ID who performed the deletion');
            
            $table->index('is_deleted', 'idx_section_is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section');
    }
};
