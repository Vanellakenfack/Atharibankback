<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select('DESCRIBE comptes');
echo "comptes table columns:\n";
foreach ($columns as $col) {
    echo "  {$col->Field} ({$col->Type}) - {$col->Null}\n";
}
