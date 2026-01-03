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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur API pour la gestion des comptes bancaires
 *
 * Gère le CRUD complet et le processus d'ouverture en 4 étapes
 */
class CompteController extends Controller
{
    protected CompteService $compteService;
    protected DocumentService $documentService;

    public function __construct(
        CompteService $compteService,
        DocumentService $documentService
    ) {
        $this->compteService = $compteService;
        $this->documentService = $documentService;
    }

    /**
     * GET /api/comptes
     * Lister tous les comptes avec filtres
     */
    public function index(IndexCompteRequest $request): JsonResponse
    {
        $query = Compte::with(['client', 'typeCompte', 'planComptable', 'mandataires']);

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
            'planComptable',
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
        try {
            // Initialiser la session d'ouverture
            $this->ouvertureSession->initSession();

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
                    'current_step' => 1
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initialisation de l\'ouverture de compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initialisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/comptes/etape1/valider
     * Valider l'étape 1
     */
    public function validerEtape1(ValiderEtape1Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $donneesValidees = $request->validated();

            // Générer le numéro de compte
            $numeroCompte = $this->compteService->genererNumeroCompte(
                $donneesValidees['client_id'],
                $donneesValidees['code_type_compte']
            );

            $client = Client::findOrFail($donneesValidees['client_id']);

            // Mettre à jour la session avec les données de l'étape 1
            $this->ouvertureSession->updateEtape(1, array_merge(
                $donneesValidees,
                ['numero_compte_genere' => $numeroCompte]
            ));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Étape 1 validée avec succès',
                'data' => [
                    'donnees' => $donneesValidees,
                    'numero_compte_genere' => $numeroCompte,
                    'client' => $client,
                    'current_step' => 2
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la validation de l\'étape 1', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la validation de l\'étape 1.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/comptes/etape2/valider
     * Valider l'étape 2
     */
    public function validerEtape2(ValiderEtape2Request $request): JsonResponse
    {
        try {
            $donneesValidees = $request->validated();

            return response()->json([
                'success' => true,
                'message' => 'Étape 2 validée avec succès',
                'data' => array_merge($donneesValidees, [
                    'current_step' => 3
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation de l\'étape 2', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la validation de l\'étape 2.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/comptes/etape3/valider
     * Valider l'étape 3 (Mandataires)
     */
    public function validerEtape3(ValiderEtape3Request $request): JsonResponse
    {
        try {
            // Récupérer les données validées
            $validated = $request->validated();
            $mandataire1 = $validated['mandataire_1'];

            // Préparer la réponse
            $responseData = [
                'success' => true,
                'message' => 'Étape 3 validée avec succès',
                'data' => [
                    'mandataire_1' => [
                        'id' => 1, // À remplacer par l'ID réel après enregistrement en base
                        'nom' => $mandataire1['nom'],
                        'prenom' => $mandataire1['prenom'],
                        'numero_cni' => $mandataire1['numero_cni']
                    ]
                ]
            ];

            // Ajouter le mandataire 2 s'il existe
            if (isset($validated['mandataire_2'])) {
                $mandataire2 = $validated['mandataire_2'];
                $responseData['data']['mandataire_2'] = [
                    'id' => 2, // À remplacer par l'ID réel après enregistrement en base
                    'nom' => $mandataire2['nom'],
                    'prenom' => $mandataire2['prenom'],
                    'numero_cni' => $mandataire2['numero_cni']
                ];
            }

            // ICI: Logique pour enregistrer les mandataires en base de données
            // Exemple:
            // $mandataire1Model = Mandataire::create([...]);
            // $responseData['data']['mandataire_1']['id'] = $mandataire1Model->id;

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la validation de l\'étape 3: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la validation de l\'étape 3.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /api/comptes/creer
     * Créer un compte
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        try {
           $data = $request->all();
           // dd($validated['etape1']);

            $donneesEtape1 = $data['etape1'] ?? null;
        $donneesEtape2 = $data['etape2'] ?? null;
        $donneesEtape3 = $data['etape3'] ?? null;
        $donneesEtape4Raw = $data['etape4'] ?? [];

            // Traiter les uploads de documents de manière plus robuste
            $documentsUploades = [];
            if ($request->hasFile('documents')) {
                $documents = $request->file('documents');
                $typesDocuments = $validated['types_documents'] ?? [];
                $descriptions = $validated['descriptions_documents'] ?? [];

                foreach ($documents as $index => $fichier) {
                    if (!$fichier->isValid()) {
                        Log::warning('Fichier invalide reçu', ['index' => $index, 'name' => $fichier->getClientOriginalName()]);
                        continue;
                    }

                    $documentsUploades[] = [
                        'fichier' => $fichier,
                        'type_document' => $typesDocuments[$index] ?? 'document',
                        'description' => $descriptions[$index] ?? null,
                    ];
                }
            }

            // Vérifier qu'au moins un document valide a été fourni
            if (empty($documentsUploades)) {
                throw new \Exception('Aucun document valide fourni. Veuillez télécharger au moins un document.');
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
            // Log l'erreur complète pour le débogage
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['documents', 'signature']) // Exclure les fichiers du log
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
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

    /**
 * GET /api/comptes/{id}/parametres-type-compte
 * Récupérer les paramètres du type de compte pour un compte spécifique
 */
    public function getParametresTypeCompte(int $id): JsonResponse
    {
        $compte = Compte::with(['typeCompte'])->findOrFail($id);

        if (!$compte->typeCompte) {
            return response()->json([
                'success' => false,
                'message' => 'Type de compte non trouvé',
            ], 404);
        }

        $typeCompte = TypeCompte::with([
            'chapitreDebit',
            'chapitreCredit',
            'chapitreFraisOuverture',
            'chapitreFraisCarnet',
            'chapitreCommissionRetrait',
            'chapitreCommissionSms',
            'chapitreInteretCredit',
            'chapitrePenalite',
        ])->findOrFail($compte->type_compte_id);

        // Construire la réponse avec tous les paramètres
        $parametres = [
            'type_compte' => [
                'id' => $typeCompte->id,
                'code' => $typeCompte->code,
                'libelle' => $typeCompte->libelle,
                'description' => $typeCompte->description,
                'est_mata' => $typeCompte->est_mata,
                'a_vue' => $typeCompte->a_vue,
                'necessite_duree' => $typeCompte->necessite_duree,
            ],

            'frais_et_commissions' => [
                'frais_ouverture' => [
                    'actif' => $typeCompte->frais_ouverture_actif,
                    'montant' => $typeCompte->frais_ouverture,
                    'chapitre' => $typeCompte->chapitreFraisOuverture,
                ],
                'frais_carnet' => [
                    'actif' => $typeCompte->frais_carnet_actif,
                    'montant' => $typeCompte->frais_carnet,
                    'chapitre' => $typeCompte->chapitreFraisCarnet,
                ],
                'commission_mensuelle' => [
                    'actif' => $typeCompte->commission_mensuelle_actif,
                    'seuil' => $typeCompte->seuil_commission,
                    'taux_superieur' => $typeCompte->commission_si_superieur,
                    'taux_inferieur' => $typeCompte->commission_si_inferieur,
                ],
                'commission_retrait' => [
                    'actif' => $typeCompte->commission_retrait_actif,
                    'montant' => $typeCompte->commission_retrait,
                    'chapitre' => $typeCompte->chapitreCommissionRetrait,
                ],
                'commission_sms' => [
                    'actif' => $typeCompte->commission_sms_actif,
                    'montant' => $typeCompte->commission_sms,
                    'chapitre' => $typeCompte->chapitreCommissionSms,
                ],
            ],

            'interets' => [
                'actif' => $typeCompte->interets_actifs,
                'taux_annuel' => $typeCompte->taux_interet_annuel,
                'frequence_calcul' => $typeCompte->frequence_calcul_interet,
                'heure_calcul' => $typeCompte->heure_calcul_interet,
                'chapitre' => $typeCompte->chapitreInteretCredit,
            ],

            'penalites' => [
                'actif' => $typeCompte->penalite_actif,
                'taux_retrait_anticipe' => $typeCompte->penalite_retrait_anticipe,
                'chapitre' => $typeCompte->chapitrePenalite,
            ],

            'chapitres_comptables' => [
                'debit' => $typeCompte->chapitreDebit,
                'credit' => $typeCompte->chapitreCredit,
                'minimum_compte' => $typeCompte->chapitreMinimumCompte,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $parametres,
        ]);
    }
}
