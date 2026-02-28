<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SessionAgence\AgenceSession;
use Illuminate\Support\Facades\DB;

$sessions = AgenceSession::where('agence_id', 9)->with('jourComptable')->get();
if ($sessions->isEmpty()) {
    echo "NO_SESSION\n";
    // also dump any jours_comptables rows
    $jours = DB::table('jours_comptables')->where('agence_id', 9)->get();
    echo "JOURS_COUNT:" . count($jours) . "\n";
    foreach ($jours as $j) {
        echo json_encode((array)$j) . "\n";
    }
    exit(0);
}

echo "SESSIONS_COUNT:" . count($sessions) . "\n";
foreach ($sessions as $s) {
    echo "SESSION_ID:" . $s->id . " STATUT:" . $s->statut . "\n";
    $j = $s->jourComptable;
    if ($j) {
        echo "  JOUR_ID:" . $j->id . " STATUT:" . $j->statut . " DATE:" . $j->date_du_jour . "\n";
    } else {
        echo "  JOUR: null\n";
    }
}
