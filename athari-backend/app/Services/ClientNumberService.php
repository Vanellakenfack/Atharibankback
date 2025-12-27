<?php

namespace App\Services;

use App\Models\client\Client;
use App\Models\Agency;

class ClientNumberService
{
    /**
     * Génère un numéro de client unique sur 9 chiffres
     * Format: [Code Agence (3)] + [Incrément (6)]
     */
    public function generate(int $agencyId): string
    {
        // 1. Récupérer le code de l'agence (ex: 001)
        $agency = Agency::findOrFail($agencyId);
        $agencyCode = str_pad($agency->code, 3, '0', STR_PAD_LEFT);

        // 2. Compter le nombre de clients existants dans cette agence pour l'incrément
        // On ajoute 1 pour le nouveau client
        $count = Client::where('agency_id', $agencyId)->count() + 1;
        
        // 3. Formater l'incrément sur 6 chiffres (ex: 000042)
        $increment = str_pad($count, 6, '0', STR_PAD_LEFT);

        return $agencyCode . $increment;
    }
}