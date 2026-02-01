<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Services\CreditPdfService;
use Illuminate\Http\Request;

class CreditClientController extends Controller
{
    protected CreditPdfService $pdfService;

    public function __construct(CreditPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Générer PDF contrat
     */
    public function generatePdf($id)
    {
        $credit = CreditApplication::with(['client','product'])->findOrFail($id);

        $file = $this->pdfService->generateContract($credit);

        return response()->json([
            'pdf_path' => $file,
            'url' => asset("storage/{$file}")
        ]);
    }

    /**
     * Acceptation du client
     */
    public function accept(Request $request, $id)
    {
        $credit = CreditApplication::findOrFail($id);

        $credit->update([
            'statut' => 'ACCEPTE'
        ]);

        return response()->json([
            'message' => 'Conditions acceptées par le client',
            'credit' => $credit
        ]);
    }
}
