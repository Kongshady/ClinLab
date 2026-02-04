<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

return new class
{
    public function run()
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

        echo "User created successfully!\n";
        echo "Name: Jerson Agapy\n";
        echo "Email: jerson.agapy@clinlab.com\n";
        echo "Password: password123\n";
        echo "Role: MIT\n";
        echo "User ID: {$user->id}\n";
    }
};
