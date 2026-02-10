<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PatientRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Patient-specific permissions
        $permissions = [
            'patient-dashboard.access',
            'patient-results.view',
            'patient-profile.view',
            'patient-certificates.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Patient role and assign permissions
        $role = Role::firstOrCreate(['name' => 'Patient']);
        $role->syncPermissions($permissions);
    }
}
