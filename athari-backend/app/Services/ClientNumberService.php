<?php

namespace App\Services;

use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;

class ClientNumberService
{
    /**
     * Génère un numéro client unique (Ex: 005000297)
     */
    public static function generate($agencyId)
    {
        // 1. Récupérer le CODE de l'agence (ex: "005")
        $agency = DB::table('agencies')->where('id', $agencyId)->first();
        $agencyCode = $agency ? $agency->code : str_pad($agencyId, 3, '0', STR_PAD_LEFT);

        // 2. Trouver le MAX dans la colonne 'num_client' pour cette agence
        $lastNumClient = Client::where('agency_id', $agencyId)
                               ->max('num_client');

        if (!$lastNumClient) {
            // Si aucun client n'existe encore pour cette agence
            $nextNumber = 1;
        } else {
            // On extrait les 6 derniers chiffres et on fait +1
            // On utilise la fonction 'lastNumClient' qui contient maintenant la chaîne (ex: "005000296")
            $lastIncrement = (int) substr($lastNumClient, -6);
            $nextNumber = $lastIncrement + 1;
        }

        // 3. Formater l'incrément sur 6 chiffres (000297)
        $increment = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Résultat final : 005000297
        return $agencyCode . $increment;
    }
}