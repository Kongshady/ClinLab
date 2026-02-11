<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Seed the roles table with the 4 system roles.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'MIT Staff',
                'display_name' => 'MIT Staff',
                'description' => 'Manages IT infrastructure, sections, employees, and activity logs',
                'status_code' => 1,
                'created_at' => now(),
            ],
            [
                'role_name' => 'Secretary',
                'display_name' => 'Secretary',
                'description' => 'Manages patients and physicians',
                'status_code' => 1,
                'created_at' => now(),
            ],
            [
                'role_name' => 'Laboratory Manager',
                'display_name' => 'Laboratory Manager',
                'description' => 'Full access to all laboratory modules',
                'status_code' => 1,
                'created_at' => now(),
            ],
            [
                'role_name' => 'Staff-in-Charge',
                'display_name' => 'Staff-in-Charge',
                'description' => 'Handles lab operations, equipment, calibration, and inventory',
                'status_code' => 1,
                'created_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                $role
            );
        }

        $this->command->info('✅ Roles table seeded with 4 roles: MIT Staff, Secretary, Laboratory Manager, Staff-in-Charge');
    }
}
