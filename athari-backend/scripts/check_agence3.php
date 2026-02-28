<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SessionAgence\AgenceSession;
use Illuminate\Support\Facades\DB;

$agenceId = 3;

$sessions = AgenceSession::where('agence_id', $agenceId)->with('jourComptable')->get();
if ($sessions->isEmpty()) {
    echo "NO_SESSION for agency $agenceId\n";
    exit(0);
}

echo "SESSIONS_COUNT:" . count($sessions) . " for agency $agenceId\n";
foreach ($sessions as $s) {
    echo "SESSION_ID:" . $s->id . " STATUT:" . $s->statut . "\n";
    $j = $s->jourComptable;
    if ($j) {
        echo "  JOUR_ID:" . $j->id . " STATUT:" . $j->statut . " DATE:" . $j->date_du_jour . "\n";
    } else {
        echo "  JOUR: null\n";
    }
}
