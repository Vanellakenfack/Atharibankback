<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Vérifier que la colonne est bien ajoutée
$columns = DB::select('DESCRIBE comptes');
echo "Colones pertinentes pour comptes:\n";
foreach ($columns as $col) {
    if (in_array($col->Field, ['jours_comptable_id', 'date_comptable', 'date_ouverture', 'jour_comptable_id'])) {
        echo "  {$col->Field} ({$col->Type}) - {$col->Null}\n";
    }
}
