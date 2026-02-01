<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Models\CreditDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CreditDocumentController extends Controller
{
    /**
     * Upload document pour un crédit
     */
    public function store(Request $request, $creditId)
    {
        $credit = CreditApplication::findOrFail($creditId);

        $data = $request->validate([
            'type_document' => 'required|string',
            'fichier' => 'required|file|mimes:pdf,doc,docx',
        ]);

        // Sauvegarde fichier
        $path = $request->file('fichier')->store('credit_documents', 'public');

        $document = CreditDocument::create([
            'credit_application_id' => $credit->id,
            'type_document' => $data['type_document'],
            'fichier' => $path,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($document, 201);
    }

    /**
     * Lister tous les documents d’un crédit
     */
    public function index($creditId)
    {
        $credit = CreditApplication::with('documents')->findOrFail($creditId);

        return response()->json($credit->documents);
    }

    /**
     * Télécharger un document
     */
    public function download($id)
    {
        $document = CreditDocument::findOrFail($id);

        return Storage::disk('public')->download($document->fichier);
    }
}
