<?php

namespace App\Services;

use App\Models\client\Client;
use App\Models\Agency;
use Illuminate\Support\Str;

class ClientNumberService
{
    public static function generate(int $agencyId): string
    {
        // 1. Trouver l'agence
        $agency = Agency::findOrFail($agencyId);

        // 2. Extraire les 3 chiffres du code (ex: de AGE001 on tire 001)
        // On utilise preg_replace pour ne garder que les chiffres
        $agencyDigits = preg_replace('/[^0-9]/', '', $agency->code);
        $prefix = Str::substr($agencyDigits, -3); 

        // 3. Compter les clients de cette agence pour le numéro d'ordre
        $count = Client::where('agency_id', $agencyId)->count();
        $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        return $prefix . $sequence;
    }
}