<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$user = \App\Models\User::first();
if (!$user) {
    echo "NO_USER\n";
    exit(0);
}

Auth::loginUsingId($user->id);

// Récupérer un client et un type de compte
$client = \App\Models\client\Client::first();
$typeCompte = \App\Models\compte\TypeCompte::first();
$gestionnaire = \App\Models\Gestionnaire::first();
$planComptable = \App\Models\chapitre\PlanComptable::first();

if (!$client || !$typeCompte || !$gestionnaire || !$planComptable) {
    echo "MISSING_DATA: client=$client, typeCompte=$typeCompte, gestionnaire=$gestionnaire, planComptable=$planComptable\n";
    exit(0);
}

echo "Client: {$client->id}, TypeCompte: {$typeCompte->id}, Gestionnaire: {$gestionnaire->id}, PlanComptable: {$planComptable->id}\n";

// Créer un compte test
try {
    $compte = \App\Models\compte\Compte::create([
        'numero_compte' => '003000001001A', // Format valide
        'client_id' => $client->id,
        'type_compte_id' => $typeCompte->id,
        'plan_comptable_id' => $planComptable->id,
        'devise' => 'FCFA',
        'gestionnaire_id' => $gestionnaire->id,
        'created_by' => $user->id,
        'statut' => 'en_attente',
        'est_en_opposition' => false,
        'validation_chef_agence' => false,
        'validation_juridique' => false,
        'solde' => 0,
        'notice_acceptee' => false,
        'date_ouverture' => now(),
    ]);
    
    echo "SUCCESS: Compte créé avec ID {$compte->id}\n";
    echo "  - numero_compte: {$compte->numero_compte}\n";
    echo "  - jours_comptable_id: {$compte->jours_comptable_id}\n";
    echo "  - date_comptable: {$compte->date_comptable}\n";
} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString();
}
