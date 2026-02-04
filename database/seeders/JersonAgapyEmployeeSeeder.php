<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class JersonAgapyEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the employee
        $employee = Employee::create([
            'firstname' => 'Jerson',
            'middlename' => null,
            'lastname' => 'Agapy',
            'username' => 'jerson.agapy',
            'password' => Hash::make('password123'),
            'position' => 'IT Specialist',
            'role' => 'MIT',
            'status_code' => 1,
            'section_id' => null,
            'role_id' => null,
        ]);

        $this->command->info('Employee created successfully!');
        $this->command->info('Name: Jerson Agapy');
        $this->command->info('Username: jerson.agapy');
        $this->command->info('Password: password123');
        $this->command->info('Position: IT Specialist');
        $this->command->info('Role: MIT');
        $this->command->info('Employee ID: ' . $employee->employee_id);
    }
}
