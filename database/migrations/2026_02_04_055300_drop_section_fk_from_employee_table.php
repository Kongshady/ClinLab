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
        Schema::table('employee', function (Blueprint $table) {
            $table->dropForeign('fk_employee_section');
            $table->dropForeign('fk_employee_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->foreign('section_id', 'fk_employee_section')->references('section_id')->on('section');
            $table->foreign('status_code', 'fk_employee_status')->references('status_code')->on('status_code');
        });
    }
};
