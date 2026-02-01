<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "All roles:\n";
$roles = Spatie\Permission\Models\Role::all();
foreach ($roles as $role) {
    echo "- " . $role->name . "\n";
}

echo "\nAdmin user roles:\n";
$admin = App\Models\User::where('email', 'admin@example.com')->first();
if ($admin) {
    $userRoles = $admin->roles;
    if ($userRoles->count() > 0) {
        foreach ($userRoles as $role) {
            echo "- " . $role->name . "\n";
        }
    } else {
        echo "No roles assigned. Assigning Admin role...\n";
        $adminRole = Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
            echo "Admin role assigned to admin@example.com\n";
        } else {
            echo "Admin role not found in database\n";
        }
    }
} else {
    echo "Admin user not found\n";
}
