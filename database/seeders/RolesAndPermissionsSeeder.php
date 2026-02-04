<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            // Module Access Permissions
            'patients.access',
            'physicians.access',
            'lab-results.access',
            'transactions.access',
            'items.access',
            'equipment.access',
            'calibration.access',
            'reports.access',
            'tests.access',
            'sections.access',
            'employees.access',
            'certificates.access',
            'activity-logs.access',

            // CRUD Permissions - Patients
            'patients.create',
            'patients.edit',
            'patients.view',
            'patients.delete',

            // CRUD Permissions - Physicians
            'physicians.create',
            'physicians.edit',
            'physicians.delete',

            // CRUD Permissions - Lab Results
            'lab-results.create',
            'lab-results.edit',
            'lab-results.delete',

            // CRUD Permissions - Transactions
            'transactions.create',
            'transactions.edit',
            'transactions.delete',

            // CRUD Permissions - Items
            'items.create',
            'items.edit',
            'items.delete',

            // CRUD Permissions - Equipment
            'equipment.create',
            'equipment.edit',
            'equipment.delete',

            // CRUD Permissions - Calibration
            'calibration.create',
            'calibration.edit',
            'calibration.delete',

            // CRUD Permissions - Certificates
            'certificates.create',
            'certificates.edit',
            'certificates.delete',

            // CRUD Permissions - Tests
            'tests.create',
            'tests.edit',
            'tests.delete',

            // CRUD Permissions - Sections
            'sections.create',
            'sections.edit',
            'sections.delete',

            // CRUD Permissions - Employees
            'employees.create',
            'employees.edit',
            'employees.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // 1. Laboratory Manager - Full access to all 14 modules
        $labManager = Role::firstOrCreate(['name' => 'Laboratory Manager', 'guard_name' => 'web']);
        $labManager->syncPermissions($permissions);

        // 2. Staff-in-Charge - Access to 10 modules
        $staffInCharge = Role::firstOrCreate(['name' => 'Staff-in-Charge', 'guard_name' => 'web']);
        $staffInCharge->syncPermissions([
            'patients.access', 'patients.create', 'patients.edit', 'patients.view', 'patients.delete',
            'physicians.access', 'physicians.create', 'physicians.edit', 'physicians.delete',
            'lab-results.access', 'lab-results.create', 'lab-results.edit', 'lab-results.delete',
            'transactions.access', 'transactions.create', 'transactions.edit', 'transactions.delete',
            'items.access', 'items.create', 'items.edit', 'items.delete',
            'equipment.access', 'equipment.create', 'equipment.edit', 'equipment.delete',
            'calibration.access', 'calibration.create', 'calibration.edit', 'calibration.delete',
            'reports.access',
            'tests.access', 'tests.create', 'tests.edit', 'tests.delete',
            'certificates.access', 'certificates.create', 'certificates.edit', 'certificates.delete',
        ]);

        // 3. MIT Staff - Access to 3 modules (sections, employees, activity logs)
        $mitStaff = Role::firstOrCreate(['name' => 'MIT', 'guard_name' => 'web']);
        $mitStaff->syncPermissions([
            'sections.access', 'sections.create', 'sections.edit', 'sections.delete',
            'employees.access', 'employees.create', 'employees.edit', 'employees.delete',
            'activity-logs.access',
        ]);

        // 4. Secretary - Access to 2 modules (patients, physicians)
        $secretary = Role::firstOrCreate(['name' => 'Secretary', 'guard_name' => 'web']);
        $secretary->syncPermissions([
            'patients.access', 'patients.create', 'patients.edit', 'patients.view',
            'physicians.access', 'physicians.create', 'physicians.edit',
        ]);

        // Create demo users (optional - remove in production)
        $this->createDemoUsers($labManager, $staffInCharge, $mitStaff, $secretary);
        
        $this->command->info('');
        $this->command->info('✅ Role-Based Access Control Setup Complete!');
        $this->command->info('');
        $this->command->info('📊 Roles & Module Access:');
        $this->command->info('1. Laboratory Manager - All 13 modules (Full Access)');
        $this->command->info('2. Staff-in-Charge - 10 modules');
        $this->command->info('3. MIT Staff - 3 modules (Sections, Employees, Activity Logs)');
        $this->command->info('4. Secretary - 2 modules (Patients, Physicians)');
    }

    /**
     * Create demo users for testing
     */
    private function createDemoUsers($labManager, $staffInCharge, $mitStaff, $secretary): void
    {
        // Laboratory Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@clinlab.test'],
            [
                'name' => 'Laboratory Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles([$labManager]);

        // Staff-in-Charge
        $staff = User::updateOrCreate(
            ['email' => 'staff@clinlab.test'],
            [
                'name' => 'Nicole Calayo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles([$staffInCharge]);

        // MIT Staff
        $mit = User::updateOrCreate(
            ['email' => 'mit@clinlab.test'],
            [
                'name' => 'MIT Staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $mit->syncRoles([$mitStaff]);

        // Secretary
        $sec = User::updateOrCreate(
            ['email' => 'secretary@clinlab.test'],
            [
                'name' => 'Secretary',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $sec->syncRoles([$secretary]);

        $this->command->info('');
        $this->command->info('🔐 Demo Login Credentials:');
        $this->command->info('Manager:   manager@clinlab.test / password');
        $this->command->info('Staff:     staff@clinlab.test / password');
        $this->command->info('MIT:       mit@clinlab.test / password');
        $this->command->info('Secretary: secretary@clinlab.test / password');
    }
}
