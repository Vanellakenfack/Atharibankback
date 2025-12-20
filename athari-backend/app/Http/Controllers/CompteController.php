<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compte\IndexCompteRequest;
use App\Http\Requests\Compte\ShowCompteRequest;
use App\Http\Requests\Compte\InitOuvertureCompteRequest;
use App\Http\Requests\Compte\ValiderEtape1Request;
use App\Http\Requests\Compte\ValiderEtape2Request;
use App\Http\Requests\Compte\ValiderEtape3Request;
use App\Http\Requests\Compte\StoreCompteRequest;
use App\Http\Requests\Compte\UpdateCompteRequest;
use App\Http\Requests\Compte\CloturerCompteRequest;
use App\Http\Requests\Compte\DestroyCompteRequest;
use App\Http\Requests\Compte\GetComptesClientRequest;
use App\Services\Compte\CompteService;
use App\Services\Compte\DocumentService;
use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\client\Client;
use App\Models\compte\DocumentCompte;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API pour la gestion des comptes bancaires
 * 
 * Gère le CRUD complet et le processus d'ouverture en 4 étapes
 */
class CompteController extends Controller
{
    protected CompteService $compteService;
    protected DocumentService $documentService;

    public function __construct(CompteService $compteService, DocumentService $documentService)
    {
        $this->compteService = $compteService;
        $this->documentService = $documentService;
    }

    /**
     * GET /api/comptes
     * Lister tous les comptes avec filtres
     */
    public function index(IndexCompteRequest $request): JsonResponse
    {
        $query = Compte::with(['client', 'typeCompte', 'chapitreComptable', 'mandataires']);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('devise')) {
            $query->where('devise', $request->devise);
        }

        if ($request->has('type_compte_id')) {
            $query->where('type_compte_id', $request->type_compte_id);
        }

        $perPage = $request->get('per_page', 15);
        $comptes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $comptes,
        ]);
    }

    /**
     * GET /api/comptes/{id}
     * Afficher un compte spécifique
     */
    public function show(ShowCompteRequest $request, int $id): JsonResponse
    {
        $compte = Compte::with([
            'client',
            'typeCompte',
            'chapitreComptable',
            'mandataires',
            'documents.uploader'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $compte,
        ]);
    }

    /**
     * POST /api/comptes/init
     * Initialiser les données pour l'ouverture d'un compte
     */
    public function initOuverture(InitOuvertureCompteRequest $request): JsonResponse
    {
        $typesComptes = TypeCompte::actif()->get();
        $devises = ['FCFA', 'EURO', 'DOLLAR', 'POUND'];
        $rubriquesMata = TypeCompte::getRubriquesMata();
        $dureesBlocage = TypeCompte::getDureesBlocage();
        $typesDocuments = DocumentCompte::TYPES_DOCUMENTS;

        return response()->json([
            'success' => true,
            'data' => [
                'types_comptes' => $typesComptes,
                'devises' => $devises,
                'rubriques_mata' => $rubriquesMata,
                'durees_blocage' => $dureesBlocage,
                'types_documents' => $typesDocuments,
            ],
        ]);
    }

    /**
     * POST /api/comptes/etape1/valider
     * Valider l'étape 1
     */
    public function validerEtape1(ValiderEtape1Request $request): JsonResponse
    {
        $donneesValidees = $request->validated();
        
        $numeroCompte = $this->compteService->genererNumeroCompte(
            $donneesValidees['client_id'],
            $donneesValidees['code_type_compte']
        );
        
        $client = Client::findOrFail($donneesValidees['client_id']);
        
        return response()->json([
            'success' => true,
            'message' => 'Étape 1 validée avec succès',
            'data' => [
                'donnees' => $donneesValidees,
                'numero_compte_genere' => $numeroCompte,
                'client' => $client,
            ],
        ]);
    }

    /**
     * POST /api/comptes/etape2/valider
     * Valider l'étape 2
     */
    public function validerEtape2(ValiderEtape2Request $request): JsonResponse
    {
        $donneesValidees = $request->validated();
        
        return response()->json([
            'success' => true,
            'message' => 'Étape 2 validée avec succès',
            'data' => $donneesValidees,
        ]);
    }

    /**
     * POST /api/comptes/etape3/valider
     * Valider l'étape 3
     */
    public function validerEtape3(ValiderEtape3Request $request): JsonResponse
    {
        $donneesValidees = $request->validated();
        
        return response()->json([
            'success' => true,
            'message' => 'Étape 3 validée avec succès',
            'data' => $donneesValidees,
        ]);
    }

    /**
     * POST /api/comptes/creer
     * Créer un compte
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $donneesEtape1 = $validated['etape1'];
            $donneesEtape2 = $validated['etape2'];
            $donneesEtape3 = $validated['etape3'];
            $donneesEtape4Raw = $validated['etape4'];
            
            // Traiter les uploads de documents
            $documentsUploades = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $fichier) {
                    $typeDocument = $validated['types_documents'][$index] ?? null;
                    $description = $validated['descriptions_documents'][$index] ?? null;
                    
                    $documentsUploades[] = [
                        'fichier' => $fichier,
                        'type_document' => $typeDocument,
                        'description' => $description,
                    ];
                }
            }
            
            // Uploader la signature
            $signaturePath = null;
            if ($request->hasFile('signature')) {
                $signaturePath = $request->file('signature')->store('signatures', 'private');
            }
            
            $donneesEtape4 = [
                'notice_acceptee' => $donneesEtape4Raw['notice_acceptee'] ?? false,
                'signature_path' => $signaturePath,
                'documents' => [],
            ];
            
            // Créer le compte
            $compte = $this->compteService->creerCompte(
                $donneesEtape1,
                $donneesEtape2,
                $donneesEtape3,
                $donneesEtape4
            );
            
            // Uploader les documents
            foreach ($documentsUploades as $docData) {
                $this->documentService->uploadDocument(
                    $compte->id,
                    $docData['fichier'],
                    $docData['type_document'],
                    $docData['description'],
                    auth()->id()
                );
            }
            
            $compte->load('documents');
            
            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => $compte,
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/comptes/{id}
     * Mettre à jour un compte
     */
    public function update(UpdateCompteRequest $request, int $id): JsonResponse
    {
        try {
            $donnees = $request->validated();
            $compte = $this->compteService->mettreAJourCompte($id, $donnees);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'data' => $compte,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/comptes/{id}/cloturer
     * Clôturer un compte
     */
    public function cloturer(CloturerCompteRequest $request, int $id): JsonResponse
    {
        try {
            $motif = $request->input('motif');
            $compte = $this->compteService->cloturerCompte($id, $motif);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte clôturé avec succès',
                'data' => $compte,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la clôture',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/comptes/{id}
     * Supprimer un compte
     */
    public function destroy(DestroyCompteRequest $request, int $id): JsonResponse
    {
        try {
            $compte = Compte::findOrFail($id);
            
            if ($compte->solde != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un compte avec un solde non nul',
                ], 400);
            }
            
            $compte->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/clients/{clientId}/comptes
     * Obtenir tous les comptes d'un client
     */
    public function getComptesClient(GetComptesClientRequest $request, int $clientId): JsonResponse
    {
        $comptes = $this->compteService->getComptesClient($clientId);
        
        return response()->json([
            'success' => true,
            'data' => $comptes,
        ]);
    }
}