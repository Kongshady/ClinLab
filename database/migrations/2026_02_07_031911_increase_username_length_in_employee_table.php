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
        // SQL Server requires dropping all indexes on the column before altering it
        \DB::statement('DROP INDEX IF EXISTS uq_employee_username ON employee');
        \DB::statement('DROP INDEX IF EXISTS employee_username_index ON employee');

        Schema::table('employee', function (Blueprint $table) {
            $table->string('username', 100)->change();
        });

        // Re-create the unique index
        Schema::table('employee', function (Blueprint $table) {
            $table->unique('username', 'uq_employee_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->string('username', 20)->change();
        });
    }
};
