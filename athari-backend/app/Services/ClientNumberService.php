<?php

namespace App\Services;

use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;

class ClientNumberService
{
    public static function generate($agencyId)
    {
        // 1. Récupérer le CODE réel de l'agence (ex: "001") via son ID
        $agency = DB::table('agencies')->where('id', $agencyId)->first();
        
        // Sécurité : si l'agence n'est pas trouvée, on peut gérer une erreur ou un code par défaut
        $agencyCode = $agency ? $agency->code : str_pad($agencyId, 3, '0', STR_PAD_LEFT);

        // 2. Compter les clients de cette agence pour l'incrément 
        $count = Client::where('agency_id', $agencyId)->count();
        $nextNumber = $count + 1;

        // 3. Formater l'incrément sur 6 chiffres 
        $increment = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Résultat : CodeAgence (3 chars) + Incrément (6 chars)
        return $agencyCode . $increment;
    }
}