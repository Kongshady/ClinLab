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

        // Create or get the MIT Staff role
        $role = Role::firstOrCreate([
            'name' => 'MIT Staff',
            'guard_name' => 'web'
        ]);

        // Assign the role to the user
        $user->assignRole('MIT Staff');

        echo "User created successfully!\n";
        echo "Name: Jerson Agapy\n";
        echo "Email: jerson.agapy@clinlab.com\n";
        echo "Password: password123\n";
        echo "Role: MIT Staff\n";
        echo "User ID: {$user->id}\n";
    }
};
