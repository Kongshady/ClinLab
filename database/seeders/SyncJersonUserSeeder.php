<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class SyncJersonUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the employee
        $employee = Employee::find(3);

        if (!$employee) {
            $this->command->error('Employee ID 3 not found!');
            return;
        }

        // Create or update the user
        $user = User::updateOrCreate(
            ['email' => 'jagapay@gmail.com'],
            [
                'name' => 'Jerson Agapay',
                'email' => 'jagapay@gmail.com',
                'password' => $employee->password, // Use same hashed password
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('User account created for login!');
        $this->command->info('Email: jagapay@gmail.com');
        $this->command->info('Password: password123');
        $this->command->info('User ID: ' . $user->id);
        $this->command->info('Employee ID: ' . $employee->employee_id);
    }
}
