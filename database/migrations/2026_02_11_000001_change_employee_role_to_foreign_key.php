<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes the employee table to use role_id as a foreign key 
     * referencing the roles table, instead of storing role as a text string.
     */
    public function up(): void
    {
        // Rename Spatie 'MIT' role to 'MIT Staff' for consistency
        DB::table('user_roles')
            ->where('name', 'MIT')
            ->update(['name' => 'MIT Staff']);

        // First, seed the roles table if empty
        $rolesExist = DB::table('roles')->count();
        if ($rolesExist === 0) {
            DB::table('roles')->insert([
                ['role_name' => 'MIT Staff', 'display_name' => 'MIT Staff', 'description' => 'Manages IT infrastructure, sections, employees, and activity logs', 'status_code' => 1, 'created_at' => now()],
                ['role_name' => 'Secretary', 'display_name' => 'Secretary', 'description' => 'Manages patients and physicians', 'status_code' => 1, 'created_at' => now()],
                ['role_name' => 'Laboratory Manager', 'display_name' => 'Laboratory Manager', 'description' => 'Full access to all laboratory modules', 'status_code' => 1, 'created_at' => now()],
                ['role_name' => 'Staff-in-Charge', 'display_name' => 'Staff-in-Charge', 'description' => 'Handles lab operations, equipment, calibration, and inventory', 'status_code' => 1, 'created_at' => now()],
            ]);
        }

        // Map existing employees with string roles to the new role_id
        // Match by the role string column to find the corresponding roles.role_id
        $roleMap = DB::table('roles')->pluck('role_id', 'role_name')->toArray();
        
        if (!empty($roleMap)) {
            foreach ($roleMap as $roleName => $roleId) {
                DB::table('employee')
                    ->where('role', $roleName)
                    ->update(['role_id' => $roleId]);
            }
            
            // Default any unmatched employees to the first role
            $firstRoleId = DB::table('roles')->orderBy('role_id')->value('role_id');
            if ($firstRoleId) {
                DB::table('employee')
                    ->whereNull('role_id')
                    ->update(['role_id' => $firstRoleId]);
            }
        }

        // Drop the old string 'role' column
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Add foreign key constraint on role_id
        Schema::table('employee', function (Blueprint $table) {
            $table->foreign('role_id', 'fk_employee_role')
                  ->references('role_id')
                  ->on('roles')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key
        Schema::table('employee', function (Blueprint $table) {
            $table->dropForeign('fk_employee_role');
        });

        // Re-add the role string column
        Schema::table('employee', function (Blueprint $table) {
            $table->string('role', 20)->default('Staff')->after('position');
        });

        // Restore role names from the roles table
        $roles = DB::table('roles')->pluck('role_name', 'role_id')->toArray();
        foreach ($roles as $roleId => $roleName) {
            DB::table('employee')
                ->where('role_id', $roleId)
                ->update(['role' => $roleName]);
        }
    }
};
