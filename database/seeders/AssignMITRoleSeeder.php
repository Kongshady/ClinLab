<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignMITRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get user ID 6
        $user = User::find(6);

        if (!$user) {
            $this->command->error('User ID 6 not found!');
            return;
        }

        // Check if user already has MIT role
        if ($user->hasRole('MIT Staff')) {
            $this->command->info('User already has MIT Staff role.');
        } else {
            // Create or get the MIT Staff role
            $role = Role::firstOrCreate([
                'name' => 'MIT Staff',
                'guard_name' => 'web'
            ]);

            // Assign the role to the user
            $user->assignRole('MIT Staff');
            $this->command->info('MIT Staff role assigned to user!');
        }

        $this->command->info('User: ' . $user->name);
        $this->command->info('Email: ' . $user->email);
        $this->command->info('Roles: ' . implode(', ', $user->getRoleNames()->toArray()));
    }
}
