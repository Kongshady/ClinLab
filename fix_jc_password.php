<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    // Find the user with email jc@gmail.com
    $user = User::where('email', 'jc@gmail.com')->first();
    
    if (!$user) {
        echo "❌ User with email 'jc@gmail.com' not found!\n";
        exit(1);
    }

    // Update the password to use Bcrypt hashing
    $user->password = Hash::make('password');
    $user->save();

    echo "✅ Password updated successfully for jc@gmail.com!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📧 Email:    jc@gmail.com\n";
    echo "🔒 Password: password\n";
    echo "👤 Name:     {$user->name}\n";
    echo "🆔 User ID:  {$user->id}\n";
    echo "🎭 Roles:    " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\nYou can now login with:\n";
    echo "Email: jc@gmail.com\n";
    echo "Password: password\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
