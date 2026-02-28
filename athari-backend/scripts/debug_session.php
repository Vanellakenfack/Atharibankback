<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SessionAgence\AgenceSession;
use App\Models\SessionAgence\JourComptable;

$session = AgenceSession::where('agence_id', 3)
    ->where('statut', 'OU')
    ->with('jourComptable')
    ->first();

if (!$session) {
    echo "No session for agency 3\n";
    exit;
}

echo "Session ID: {$session->id}\n";
echo "Agence ID: {$session->agence_id}\n";
echo "Statut: {$session->statut}\n";
echo "Statut is 'OUVERT'?: " . ($session->statut === 'OUVERT' ? 'YES' : 'NO - actual: ' . $session->statut) . "\n";
echo "Jour Comptable: " . ($session->jourComptable ? 'EXISTS' : 'NULL') . "\n";

if ($session->jourComptable) {
    echo "  JC ID: {$session->jourComptable->id}\n";
    echo "  JC Statut: {$session->jourComptable->statut}\n";
    echo "  JC Statut is 'OUVERT'?: " . ($session->jourComptable->statut === 'OUVERT' ? 'YES' : 'NO - actual: ' . $session->jourComptable->statut) . "\n";
} else {
    echo "  No jour comptable loaded\n";
}

// Try loading directly
if ($session->jours_comptable_id) {
    $jc = JourComptable::find($session->jours_comptable_id);
    echo "\nDirect JourComptable lookup:\n";
    if ($jc) {
        echo "  JC ID: {$jc->id}\n";
        echo "  JC Statut: {$jc->statut}\n";
        echo "  JC Statut is 'OUVERT'?: " . ($jc->statut === 'OUVERT' ? 'YES' : 'NO - actual: ' . $jc->statut) . "\n";
    } else {
        echo "  JourComptable with ID {$session->jours_comptable_id} not found\n";
    }
}
