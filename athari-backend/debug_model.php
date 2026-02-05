<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tc = \App\Models\compte\TypeCompte::find(1);
if (!$tc) {
    echo "TypeCompte avec ID 1 non trouvé" . PHP_EOL;
    exit(1);
}

$attrs = $tc->getAttributes();
echo 'Nombre d\'attributs: ' . count($attrs) . PHP_EOL;
echo PHP_EOL . 'Attributs avec "renouvellement":' . PHP_EOL;

$found = false;
foreach(array_keys($attrs) as $key) {
  if (strpos($key, 'renouvellement') !== false) {
    echo '  FOUND: ' . $key . ' = ' . $attrs[$key] . PHP_EOL;
    $found = true;
  }
}

if (!$found) {
  echo '  MISSING: Aucun attribut avec "renouvellement" trouvé' . PHP_EOL;
}

echo PHP_EOL . 'Conversion toArray():' . PHP_EOL;
$array = $tc->toArray();
echo 'Nombre de clés: ' . count($array) . PHP_EOL;

foreach(['frais_renouvellement_actif', 'chapitre_renouvellement_id', 'frais_renouvellement_carnet', 'frais_renouvellement_livret'] as $field) {
    if (isset($array[$field])) {
        echo '  ✓ ' . $field . ' = ' . $array[$field] . PHP_EOL;
    } else {
        echo '  ✗ ' . $field . ' MANQUANT' . PHP_EOL;
    }
}
