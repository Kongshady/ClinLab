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

        // For SQL Server: drop ALL check constraints on the status column (auto-generated names vary)
        $constraints = DB::select("SELECT cc.name FROM sys.check_constraints cc JOIN sys.columns c ON cc.parent_object_id = c.object_id AND cc.parent_column_id = c.column_id WHERE cc.parent_object_id = OBJECT_ID('certificate_issues') AND c.name = 'status'");
        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE certificate_issues DROP CONSTRAINT [{$constraint->name}]");
        }

        // Change column to nvarchar to support Pending status
        Schema::table('certificate_issues', function (Blueprint $table) {
            $table->string('status', 20)->default('Pending')->change();
        });

        // Add new CHECK constraint that includes Pending
        DB::statement("ALTER TABLE certificate_issues ADD CONSTRAINT CK_certificate_issues_status CHECK (status IN ('Issued', 'Revoked', 'Expired', 'Pending'))");

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
