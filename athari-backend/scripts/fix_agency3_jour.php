<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check agency 3 sessions
$sessions = DB::table('agence_sessions')
    ->where('agence_id', 3)
    ->select('id', 'agence_id', 'statut', 'jours_comptable_id', 'date_comptable')
    ->get();

echo "Agency 3 sessions:\n";
foreach ($sessions as $sess) {
    echo "  ID: {$sess->id}, STATUT: {$sess->statut}, jours_comptable_id: " . ($sess->jours_comptable_id ?? 'NULL') . ", DATE: {$sess->date_comptable}\n";
}

echo "\nJours comptables for agency 3:\n";
$jours = DB::table('jours_comptables')
    ->where('agence_id', 3)
    ->select('id', 'agence_id', 'statut', 'date_du_jour')
    ->get();

foreach ($jours as $jour) {
    echo "  ID: {$jour->id}, STATUT: {$jour->statut}, DATE: {$jour->date_du_jour}\n";
}

if (count($jours) === 0) {
    echo "\nNo jours_comptables for agency 3, creating one...\n";
    $id = DB::table('jours_comptables')->insertGetId([
        'agence_id' => 3,
        'statut' => 'OUVERT',
        'date_du_jour' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created jour_comptable ID: $id\n";
    
    // Update session with jours_comptable_id
    $updated = DB::table('agence_sessions')
        ->where('agence_id', 3)
        ->where('statut', 'OU')
        ->update(['jours_comptable_id' => $id]);
    echo "Updated $updated sessions with jours_comptable_id\n";
} else {
    echo "\nJours comptables exist, checking if sessions are linked...\n";
    $unlinked = DB::table('agence_sessions')
        ->where('agence_id', 3)
        ->where('statut', 'OU')
        ->whereNull('jours_comptable_id')
        ->count();
    if ($unlinked > 0) {
        echo "Found $unlinked unlinked OU sessions, linking to jour_comptable...\n";
        $jour = $jours[0];
        $updated = DB::table('agence_sessions')
            ->where('agence_id', 3)
            ->where('statut', 'OU')
            ->whereNull('jours_comptable_id')
            ->update(['jours_comptable_id' => $jour->id]);
        echo "Linked $updated sessions to jour_comptable ID {$jour->id}\n";
    } else {
        echo "All OU sessions are already linked.\n";
    }
}
