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
            'inventory.access',
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
            'patients.update',
            'patients.delete',

            // CRUD Permissions - Physicians
            'physicians.create',
            'physicians.update',
            'physicians.delete',

            // CRUD Permissions - Lab Results
            'lab-results.create',
            'lab-results.update',
            'lab-results.delete',
            'lab-results.verify',

            // CRUD Permissions - Transactions
            'transactions.create',
            'transactions.update',
            'transactions.delete',
            'transactions.void',

            // CRUD Permissions - Items
            'items.create',
            'items.update',
            'items.delete',

            // Inventory Permissions
            'inventory.stock-in',
            'inventory.stock-out',
            'inventory.usage',
            'inventory.adjust',

            // CRUD Permissions - Equipment
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            'equipment.maintenance',

            // CRUD Permissions - Calibration
            'calibration.create',
            'calibration.update',
            'calibration.delete',

            // CRUD Permissions - Certificates
            'certificates.create',
            'certificates.update',
            'certificates.delete',
            'certificates.issue',
            'certificates.revoke',

            // CRUD Permissions - Tests
            'tests.create',
            'tests.update',
            'tests.delete',

            // CRUD Permissions - Sections
            'sections.create',
            'sections.update',
            'sections.delete',

            // CRUD Permissions - Employees
            'employees.create',
            'employees.update',
            'employees.delete',

            // Reports Permissions
            'reports.generate',
            'reports.export',

            // Activity Logs Permissions
            'activity-logs.view',
            'activity-logs.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Staff-in-Charge Role
        $staffInCharge = Role::create(['name' => 'Staff-in-Charge']);
        $staffInCharge->givePermissionTo([
            'patients.access',
            'patients.create',
            'patients.update',
            'patients.delete',
            
            'physicians.access',
            'physicians.create',
            'physicians.update',
            'physicians.delete',
            
            'lab-results.access',
            'lab-results.create',
            'lab-results.update',
            'lab-results.delete',
            'lab-results.verify',
            
            'transactions.access',
            'transactions.create',
            'transactions.update',
            'transactions.delete',
            'transactions.void',
            
            'items.access',
            'items.create',
            'items.update',
            'items.delete',
            
            'inventory.access',
            'inventory.stock-in',
            'inventory.stock-out',
            'inventory.usage',
            'inventory.adjust',
            
            'equipment.access',
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            'equipment.maintenance',
            
            'calibration.access',
            'calibration.create',
            'calibration.update',
            'calibration.delete',
            
            'reports.access',
            'reports.generate',
            'reports.export',
        ]);

        // 2. MIT Staff Role
        $mitStaff = Role::create(['name' => 'MIT Staff']);
        $mitStaff->givePermissionTo([
            'tests.access',
            'tests.create',
            'tests.update',
            'tests.delete',
            
            'sections.access',
            'sections.create',
            'sections.update',
            'sections.delete',
            
            'employees.access',
            'employees.create',
            'employees.update',
            'employees.delete',
        ]);

        // 3. Laboratory Manager Role
        $labManager = Role::create(['name' => 'Laboratory Manager']);
        $labManager->givePermissionTo([
            'patients.access',
            'patients.create',
            'patients.update',
            'patients.delete',
            
            'physicians.access',
            'physicians.create',
            'physicians.update',
            'physicians.delete',
            
            'lab-results.access',
            'lab-results.create',
            'lab-results.update',
            'lab-results.delete',
            'lab-results.verify',
            
            'transactions.access',
            'transactions.create',
            'transactions.update',
            'transactions.delete',
            'transactions.void',
            
            'items.access',
            'items.create',
            'items.update',
            'items.delete',
            
            'inventory.access',
            'inventory.stock-in',
            'inventory.stock-out',
            'inventory.usage',
            'inventory.adjust',
            
            'equipment.access',
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            'equipment.maintenance',
            
            'calibration.access',
            'calibration.create',
            'calibration.update',
            'calibration.delete',
            
            'certificates.access',
            'certificates.create',
            'certificates.update',
            'certificates.delete',
            'certificates.issue',
            'certificates.revoke',
            
            'reports.access',
            'reports.generate',
            'reports.export',
            
            'activity-logs.access',
            'activity-logs.view',
            'activity-logs.delete',
        ]);

        // 4. Secretary Role
        $secretary = Role::create(['name' => 'Secretary']);
        $secretary->givePermissionTo([
            'transactions.access',
            'transactions.create',
            'transactions.update',
            
            'inventory.access',
        ]);

        // Create demo users (optional - remove in production)
        $this->createDemoUsers($labManager, $staffInCharge, $mitStaff, $secretary);
    }

    /**
     * Create demo users for testing
     */
    private function createDemoUsers($labManager, $staffInCharge, $mitStaff, $secretary): void
    {
        // Laboratory Manager
        $manager = User::create([
            'name' => 'Laboratory Manager',
            'email' => 'manager@clinlab.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $manager->assignRole($labManager);

        // Staff-in-Charge
        $staff = User::create([
            'name' => 'Nicole Calayo',
            'email' => 'staff@clinlab.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $staff->assignRole($staffInCharge);

        // MIT Staff
        $mit = User::create([
            'name' => 'MIT Staff',
            'email' => 'mit@clinlab.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $mit->assignRole($mitStaff);

        // Secretary
        $sec = User::create([
            'name' => 'Secretary',
            'email' => 'secretary@clinlab.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $sec->assignRole($secretary);

        $this->command->info('Demo users created:');
        $this->command->info('Manager: manager@clinlab.test / password');
        $this->command->info('Staff: staff@clinlab.test / password');
        $this->command->info('MIT: mit@clinlab.test / password');
        $this->command->info('Secretary: secretary@clinlab.test / password');
    }
}
