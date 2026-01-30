<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simuler une mise à jour
$tc = \App\Models\compte\TypeCompte::find(1);
if (!$tc) {
    echo "TypeCompte 1 not found" . PHP_EOL;
    exit(1);
}

// Mettre à jour un champ
$tc->update(['frais_renouvellement_actif' => 0]);

// Refresh
$tc->refresh();

// Charger les relations
$tc->load(['chapitreRenouvellement']);

// Convertir en array
$data = $tc->toArray();

// Vérifier les champs
echo "Vérification des champs de renouvellement:" . PHP_EOL;
$fields = ['frais_renouvellement_carnet', 'frais_renouvellement_livret', 'frais_renouvellement_actif', 'chapitre_renouvellement_id'];
foreach ($fields as $field) {
    if (isset($data[$field])) {
        echo "✓ $field = " . json_encode($data[$field]) . PHP_EOL;
    } else {
        echo "✗ $field MANQUANT" . PHP_EOL;
    }
}

// Vérifier le JSON
echo PHP_EOL . "JSON:" . PHP_EOL;
$json = json_encode(['data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$lines = explode(PHP_EOL, $json);
foreach ($lines as $line) {
    if (strpos($line, 'renouvellement') !== false) {
        echo $line . PHP_EOL;
    }
}
