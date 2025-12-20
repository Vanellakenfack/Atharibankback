<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Compte\DocumentService;
use App\Models\compte\DocumentCompte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API pour la gestion des documents de compte
 */
class DocumentCompteController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * GET /api/comptes/{compteId}/documents
     * Lister les documents d'un compte
     * 
     * @param int $compteId
     * @return JsonResponse
     */
    public function index(int $compteId): JsonResponse
    {
        $documents = DocumentCompte::where('compte_id', $compteId)
            ->with('uploader')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * POST /api/comptes/{compteId}/documents
     * Uploader un nouveau document
     * 
     * @param Request $request
     * @param int $compteId
     * @return JsonResponse
     */
    public function store(Request $request, int $compteId): JsonResponse
    {
        try {
            $request->validate([
                'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'type_document' => 'required|string',
                'description' => 'nullable|string',
            ]);

            $document = $this->documentService->uploadDocument(
                $compteId,
                $request->file('fichier'),
                $request->type_document,
                $request->description,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => $document,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/documents/{id}/telecharger
     * Télécharger un document
     * 
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(int $id)
    {
        return $this->documentService->telechargerDocument($id);
    }

    /**
     * DELETE /api/documents/{id}
     * Supprimer un document
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->documentService->supprimerDocument($id);

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}