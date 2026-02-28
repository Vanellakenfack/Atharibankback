<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

$agencyId = 3;
$now = Carbon::now();

// Vérifier que l'agence 3 existe
if (! DB::table('agencies')->where('id', $agencyId)->exists()) {
    echo "Agency $agencyId does not exist!\n";
    exit(1);
}

// Récupérer tous les users
$userIds = DB::table('users')->pluck('id');

if ($userIds->isEmpty()) {
    echo "No users found!\n";
    exit(0);
}

echo "Found " . count($userIds) . " users\n";

$inserts = [];

foreach ($userIds as $userId) {
    $exists = DB::table('agency_user')
        ->where('user_id', $userId)
        ->where('agency_id', $agencyId)
        ->exists();

    if (! $exists) {
        $inserts[] = [
            'user_id'    => $userId,
            'agency_id'  => $agencyId,
            'is_primary' => true,
            'assigned_at'=> $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

if (! empty($inserts)) {
    DB::table('agency_user')->insert($inserts);
    echo "Inserted " . count($inserts) . " agency_user records for agency $agencyId\n";
} else {
    echo "All users already assigned to agency $agencyId\n";
}

echo "Done!\n";
