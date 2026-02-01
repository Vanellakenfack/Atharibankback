<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::firstOrCreate(
    ['email' => 'agent@test.com'],
    [
        'name' => 'Test Agent',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

$user->assignRole('agent_credit');

echo "User created and role assigned: " . $user->email . "\n";
echo "Role assigned: agent_credit\n";
