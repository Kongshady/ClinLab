<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the index that depends on the status column
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->dropIndex('certificate_issues_status_issued_at_index');
        });

        // For SQL Server: drop any check constraint on status
        DB::statement("ALTER TABLE certificate_issues DROP CONSTRAINT IF EXISTS CK__certifica__statu__certificate_issues");

        // Change column to nvarchar to support Pending status
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->string('status', 20)->default('Pending')->change();
        });

        // Recreate the index
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->index(['status', 'issued_at']);
        });

        // Add approved_by and approved_at columns
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('generated_by');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approved_at']);
        });
    }
};
