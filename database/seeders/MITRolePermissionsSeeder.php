<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MITRolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            'patients.access',
            'patients.create',
            'patients.edit',
            'patients.view',
            'physicians.access',
            'lab-results.access',
            'lab-results.create',
            'tests.access',
            'tests.create',
            'tests.edit',
            'certificates.access',
            'transactions.access',
            'items.access',
            'items.create',
            'items.edit',
            'equipment.access',
            'equipment.create',
            'equipment.edit',
            'calibration.access',
            'sections.access',
            'sections.create',
            'sections.edit',
            'employees.access',
            'employees.create',
            'employees.edit',
            'reports.access',
            'activity-logs.access',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // Get MIT role
        $mitRole = Role::where('name', 'MIT Staff')->first();

        if ($mitRole) {
            // Assign all permissions to MIT role
            $mitRole->syncPermissions($permissions);
            $this->command->info('MIT role has been granted all permissions!');
            $this->command->info('Total permissions: ' . count($permissions));
        } else {
            $this->command->error('MIT role not found!');
        }
    }
}
