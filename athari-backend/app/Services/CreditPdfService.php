<?php

namespace App\Services;

use App\Models\CreditApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreditPdfService
{
    public function generateContract(CreditApplication $credit): string
    {
        $pdf = Pdf::loadView('pdf.credit_contract', [
            'credit' => $credit
        ]);

        $filename = 'contracts/CR-' . $credit->id . '-' . Str::random(5) . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }
}
