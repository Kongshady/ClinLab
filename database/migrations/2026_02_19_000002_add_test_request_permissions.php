<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add test-request permissions for Patient role
        $patientPermissions = [
            'patient-requests.access',
            'patient-requests.create',
        ];

        foreach ($patientPermissions as $perm) {
            $exists = DB::table('user_permissions')->where('name', $perm)->exists();
            if (!$exists) {
                DB::table('user_permissions')->insert([
                    'name' => $perm,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign to Patient role
        $patientRoleId = DB::table('user_roles')->where('name', 'Patient')->value('id');
        if ($patientRoleId) {
            $permIds = DB::table('user_permissions')
                ->whereIn('name', $patientPermissions)
                ->pluck('id');

            foreach ($permIds as $permId) {
                $pivotExists = DB::table('role_has_permissions')
                    ->where('role_id', $patientRoleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$pivotExists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $patientRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // Add test-requests.access and test-requests.review for staff roles
        $staffPermissions = [
            'test-requests.access',
            'test-requests.review',
        ];

        foreach ($staffPermissions as $perm) {
            $exists = DB::table('user_permissions')->where('name', $perm)->exists();
            if (!$exists) {
                DB::table('user_permissions')->insert([
                    'name' => $perm,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign staff permissions to Laboratory Manager and Staff-in-Charge
        $staffRoles = ['Laboratory Manager', 'Staff-in-Charge'];
        foreach ($staffRoles as $roleName) {
            $roleId = DB::table('user_roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                $permIds = DB::table('user_permissions')
                    ->whereIn('name', $staffPermissions)
                    ->pluck('id');

                foreach ($permIds as $permId) {
                    $pivotExists = DB::table('role_has_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permId)
                        ->exists();

                    if (!$pivotExists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $roleId,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $allPermissions = [
            'patient-requests.access',
            'patient-requests.create',
            'test-requests.access',
            'test-requests.review',
        ];

        $permIds = DB::table('user_permissions')
            ->whereIn('name', $allPermissions)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('user_permissions')->whereIn('name', $allPermissions)->delete();
    }
};
