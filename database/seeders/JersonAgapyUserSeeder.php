<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class JersonAgapyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the user
        $user = User::create([
            'name' => 'Jerson Agapy',
            'email' => 'jerson.agapy@clinlab.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create or get the MIT role
        $role = Role::firstOrCreate([
            'name' => 'MIT',
            'guard_name' => 'web'
        ]);

        // Assign the role to the user
        $user->assignRole('MIT');

        $this->command->info('User created successfully!');
        $this->command->info('Name: Jerson Agapy');
        $this->command->info('Email: jerson.agapy@clinlab.com');
        $this->command->info('Password: password123');
        $this->command->info('Role: MIT');
        $this->command->info('User ID: ' . $user->id);
    }
}
