<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add Patient role to the custom roles table
        $exists = DB::table('roles')->where('role_name', 'Patient')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'role_name' => 'Patient',
                'display_name' => 'Patient',
                'description' => 'Patients who log in via Google OAuth',
                'status_code' => 1,
                'created_at' => now(),
            ]);
        }

        // Add Patient role to the Spatie user_roles table
        $spatieExists = DB::table('user_roles')->where('name', 'Patient')->exists();
        if (!$spatieExists) {
            DB::table('user_roles')->insert([
                'name' => 'Patient',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Patient-specific permissions in Spatie
        $permissions = [
            'patient-dashboard.access',
            'patient-results.view',
            'patient-profile.view',
            'patient-certificates.view',
        ];

        foreach ($permissions as $perm) {
            $permExists = DB::table('user_permissions')->where('name', $perm)->exists();
            if (!$permExists) {
                DB::table('user_permissions')->insert([
                    'name' => $perm,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Assign permissions to Patient role
        $patientRoleId = DB::table('user_roles')->where('name', 'Patient')->value('id');
        if ($patientRoleId) {
            $permIds = DB::table('user_permissions')
                ->whereIn('name', $permissions)
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
    }

    public function down(): void
    {
        $patientRoleId = DB::table('user_roles')->where('name', 'Patient')->value('id');

        if ($patientRoleId) {
            DB::table('role_has_permissions')->where('role_id', $patientRoleId)->delete();
            DB::table('model_has_roles')->where('role_id', $patientRoleId)->delete();
            DB::table('user_roles')->where('id', $patientRoleId)->delete();
        }

        $permissions = [
            'patient-dashboard.access',
            'patient-results.view',
            'patient-profile.view',
            'patient-certificates.view',
        ];
        DB::table('user_permissions')->whereIn('name', $permissions)->delete();

        DB::table('roles')->where('role_name', 'Patient')->delete();
    }
};
