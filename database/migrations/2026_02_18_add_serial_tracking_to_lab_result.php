<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_result', function (Blueprint $table) {
            $table->string('serial_number', 20)->nullable()->after('status');
            $table->string('verification_code', 64)->nullable()->after('serial_number');
            $table->boolean('is_revoked')->default(false)->after('verification_code');
            $table->dateTime('printed_at')->nullable()->after('is_revoked');
        });

        // SQL Server filtered unique indexes to allow multiple NULLs
        DB::statement('CREATE UNIQUE INDEX lab_result_serial_number_unique ON lab_result (serial_number) WHERE serial_number IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX lab_result_verification_code_unique ON lab_result (verification_code) WHERE verification_code IS NOT NULL');
    }

    public function down(): void
    {
        // Drop filtered indexes first
        DB::statement('DROP INDEX IF EXISTS lab_result_serial_number_unique ON lab_result');
        DB::statement('DROP INDEX IF EXISTS lab_result_verification_code_unique ON lab_result');

        Schema::table('lab_result', function (Blueprint $table) {
            $table->dropColumn(['serial_number', 'verification_code', 'is_revoked', 'printed_at']);
        });
    }
};
