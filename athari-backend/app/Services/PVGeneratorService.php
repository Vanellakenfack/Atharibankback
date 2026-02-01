<?php

namespace App\Services;

use App\Models\CreditApplication;
use App\Models\CreditPV;
use Illuminate\Support\Str;

class PVGeneratorService
{
    public function generate(CreditApplication $credit, string $niveau): CreditPV
    {
        $numeroPV = strtoupper($niveau) . '-' . now()->format('Ymd') . '-' . Str::random(5);

        // Pour l’instant on simule un PDF
        $fakePdfPath = "pvs/{$numeroPV}.pdf";

        return CreditPV::create([
            'credit_application_id' => $credit->id,
            'niveau' => $niveau,
            'numero_pv' => $numeroPV,
            'fichier_pdf' => $fakePdfPath,
        ]);
    }
}
