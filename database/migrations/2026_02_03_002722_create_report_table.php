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
        Schema::create('report', function (Blueprint $table) {
            $table->integer('report_id', true, true)->length(10);
            $table->string('report_type', 20)->comment('Quarterly, Midyear, YearEnd');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('generated_by')->length(10);
            $table->dateTime('datetime_generated');
            $table->string('file_path', 255)->nullable();
            
            $table->primary('report_id');
            $table->index('generated_by');
            
            $table->foreign('generated_by', 'report_ibfk_1')
                  ->references('employee_id')->on('employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report');
    }
};
