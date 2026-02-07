<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

try {
    // Check if user already exists
    $existingUser = User::where('email', 'martintolangmit@gmail.com')->first();
    
    if ($existingUser) {
        echo "❌ User with email 'martintolangmit@gmail.com' already exists!\n";
        echo "User ID: {$existingUser->id}\n";
        echo "Name: {$existingUser->name}\n";
        echo "Current Roles: " . implode(', ', $existingUser->getRoleNames()->toArray()) . "\n";
        exit(1);
    }

    // Create the user
    $user = User::create([
        'name' => 'Martin Tolang',
        'email' => 'martintolangmit@gmail.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    // Get or create the MIT role
    $mitRole = Role::where('name', 'MIT')->where('guard_name', 'web')->first();
    
    if (!$mitRole) {
        echo "❌ MIT role not found! Please run the seeder first:\n";
        echo "   php artisan db:seed --class=RolesAndPermissionsSeeder\n";
        $user->delete();
        exit(1);
    }

    // Assign the MIT role to the user
    $user->assignRole('MIT');

    echo "✅ MIT User created successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📧 Email:    martintolangmit@gmail.com\n";
    echo "🔒 Password: password\n";
    echo "👤 Name:     Martin Tolang\n";
    echo "🎭 Role:     MIT Staff\n";
    echo "🆔 User ID:  {$user->id}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    echo "🚪 Login URL: http://127.0.0.1:8000/login\n";
    echo "📊 Dashboard: http://127.0.0.1:8000/dashboard/mit\n";
    echo "\n";
    echo "MIT Staff has access to:\n";
    echo "  • Sections Management\n";
    echo "  • Employees Management\n";
    echo "  • Activity Logs\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ Error creating user: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
