<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class UpdateJersonEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find employee with ID 3
        $employee = Employee::find(3);

        if ($employee) {
            // Update the employee
            $employee->update([
                'lastname' => 'Agapay',
                'username' => 'jagapay@gmail.com',
            ]);

            $this->command->info('Employee updated successfully!');
            $this->command->info('Name: Jerson Agapay');
            $this->command->info('Username: jagapay@gmail.com');
            $this->command->info('Employee ID: ' . $employee->employee_id);
        } else {
            $this->command->error('Employee ID 3 not found!');
        }
    }
}
