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
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade\Pdf;

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
    public function show(ShowCompteRequest $request, $id): JsonResponse
{
    $compte = Compte::with([
        'client.physique', // <--- IMPORTANT : charge la table clients_physiques
        'client.morale',   // <--- IMPORTANT : charge la table clients_morales
        'typeCompte',
        'documents', 
        'planComptable',
        'mandataires'
    ])->findOrFail($id);

    // Pour être sûr que React reçoive le nom même si 'appends' ne fonctionne pas
    $compte->client->makeVisible('nom_complet'); 

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

            // Défauts pour éviter des erreurs 'Undefined array key' côté service
            $donneesEtape1 = $data['etape1'] ?? [];
            $donneesEtape2 = $data['etape2'] ?? [];
            $donneesEtape3 = $data['etape3'] ?? [];
            $donneesEtape4Raw = $data['etape4'] ?? [];

            // Vérifications rapides des champs essentiels pour une erreur lisible
            if (empty($donneesEtape1) || empty($donneesEtape2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données d\'ouverture incomplètes : étapes 1 et 2 requises.',
                ], 422);
            }

            // Vérification gestionnaire_id (requis)
            if (!isset($donneesEtape2['gestionnaire_id'])) {
                Log::error('Erreur création compte : gestionnaire_id manquant', ['payload' => $data]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le champ gestionnaire_id est requis dans l\'étape 2.',
                ], 422);
            }

            // 🔹 Récupérer les infos du gestionnaire
            $gestionnaire = \App\Models\Gestionnaire::findOrFail($donneesEtape2['gestionnaire_id']);

            // 1. Traitement des fichiers (Signature et Documents) - OPTIONNELS
            $documentsUploades = [];
            if ($request->hasFile('documents')) {
                $documents = $request->file('documents');
                $typesDocuments = $data['types_documents'] ?? [];
                $descriptions = $data['descriptions_documents'] ?? [];

                foreach ($documents as $index => $fichier) {
                    if ($fichier->isValid()) {
                        $documentsUploades[] = [
                            'fichier' => $fichier,
                            'type_document' => $typesDocuments[$index] ?? 'document',
                            'description' => $descriptions[$index] ?? null,
                        ];
                    }
                }
            }

            // 2. Traitement des nouveaux fichiers PDF (demande_ouverture et formulaire_ouverture)
            $demandeOuverturePath = null;
            $formulaireOuverturePath = null;

            if ($request->hasFile('demande_ouverture_pdf')) {
                $demandeOuverturePath = $request->file('demande_ouverture_pdf')->store('demandes_ouverture', 'private');
            }

            if ($request->hasFile('formulaire_ouverture_pdf')) {
                $formulaireOuverturePath = $request->file('formulaire_ouverture_pdf')->store('formulaires_ouverture', 'private');
            }

            $signaturePath = $request->hasFile('signature') 
                ? $request->file('signature')->store('signatures', 'private') 
                : null;

            $donneesEtape4 = [
                'notice_acceptee' => $donneesEtape4Raw['notice_acceptee'] ?? false,
                'signature_path' => $signaturePath,
                'documents' => [],
                // AJOUT DES NOUVEAUX CHEMINS
                'demande_ouverture_pdf' => $demandeOuverturePath,
                'formulaire_ouverture_pdf' => $formulaireOuverturePath,
            ];

            // 3. Utilisation d'une transaction globale pour lier Création + Comptabilité
            return DB::transaction(function () use ($donneesEtape1, $donneesEtape2, $donneesEtape3, $donneesEtape4, $documentsUploades) {
                
                // ÉTAPE A : Créer le compte
                $compte = $this->compteService->creerCompte(
                    $donneesEtape1,
                    $donneesEtape2,
                    $donneesEtape3,
                    $donneesEtape4
                );

                // ÉTAPE B : Upload des documents (seulement s'il y en a)
                foreach ($documentsUploades as $docData) {
                    $this->documentService->uploadDocument(
                        $compte->id,
                        $docData['fichier'],
                        $docData['type_document'],
                        $docData['description'],
                        auth()->id()
                    );
                }

                // ÉTAPE C : TRAITEMENT COMPTABLE (Dépôt + Frais + Minimum)
                $montantInitial = floatval($donneesEtape2['solde'] ?? 0);
                $this->compteService->traiterOuvertureComptable($compte, $montantInitial);

                return response()->json([
                    'success' => true,
                    'message' => 'Compte créé et en attente de validation',
                    'data' => $compte->fresh(['documents', 'typeCompte']),
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Erreur création compte : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
                'frais_livret' => [
                    'actif' => $typeCompte->frais_livret_actif,
                    'montant' => $typeCompte->frais_livret,
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

public function getJournalOuvertures(Request $request)
{
    $dateDebut = $request->input('date_debut', now()->toDateString());
    $dateFin = $request->input('date_fin', now()->toDateString());
    $codeAgence = $request->input('code_agence');

    // Récupère une collection de COMPTES (et non de mouvements)
    $comptes = $this->compteService->journalOuvertures($dateDebut, $dateFin, $codeAgence);

    $journalFormate = $comptes->map(function ($compte) {
        // On récupère le premier mouvement de dépôt s'il existe (chargé via eager loading)
        $mvtInitial = $compte->mouvements->first();

        return [
            'agence'            => $compte->client->agency?->code ?? 'N/A',
            'date_ouverture'    => $compte->date_ouverture ? $compte->date_ouverture->format('d/m/Y H:i') : 'N/A',
            'numero_client'     => $compte->client->num_client ?? 'N/A',
            'nom_client' => $compte->client->nom_complet, 
            'numero_compte'     => $compte->numero_compte,
            'type_compte'       => $compte->typeCompte->libelle ?? 'N/A',
            'intitule_mouvement'=> $mvtInitial->libelle_mouvement ?? 'Aucun dépôt initial',
            'montant_debit'     => $mvtInitial->montant_debit ?? 0,
            'montant_credit'    => $mvtInitial->montant_credit ?? 0,
        ];
    });

    return response()->json([
        'statut' => 'success',
        'metadata' => [
            'total_comptes' => $journalFormate->count(),
            'periode' => "Du $dateDebut au $dateFin",
            'genere_le' => now()->format('d/m/Y H:i')
        ],
        'donnees' => $journalFormate
    ]);
}
public function clotureJourneeOuvertures(Request $request)
{
    $date = $request->query('date', now()->toDateString());
    // On récupère le code agence pour filtrer les calculs
    $codeAgence = $request->query('code_agence'); 
    
    // On passe le code au service
    $resume = $this->compteService->resumeClotureOuvertures($date, $codeAgence);
    
    // Calcul du grand total global (uniquement sur l'agence filtrée)
    $totalGlobalDepots = $resume->sum('total_depots');
    $totalGlobalFrais = $resume->sum('total_frais');

    return response()->json([
        'statut' => 'success',
        'metadata' => [
            'date_cloture' => $date,
            'agence' => $codeAgence ?? 'Toutes les agences',
        ],
        'resume_par_produit' => $resume,
        'synthese_financiere' => [
            'total_cash_entre' => $totalGlobalDepots,
            'total_revenu_banque' => $totalGlobalFrais,
            // Le cash physique en caisse est la somme des dépôts initiaux
            'net_a_reverser_en_coffre' => $totalGlobalDepots 
        ]
    ]);
}



public function exporterJournalPdf(Request $request)
{
    $dateDebut  = $request->input('date_debut');
    $dateFin    = $request->input('date_fin');
    $codeAgence = $request->input('code_agence');

    $comptes = $this->compteService->journalOuvertures($dateDebut, $dateFin, $codeAgence);

    $pdf = Pdf::loadView('pdf.journal', [
        'donnees'     => $comptes,
        'date_debut'  => $dateDebut,
        'date_fin'    => $dateFin,
        'code_agence' => $codeAgence
    ])->setPaper('a4', 'landscape');

    // On s'assure que le nom du fichier est propre
    $fileName = "journal_ouvertures_" . ($codeAgence ?? 'global') . ".pdf";

    return $pdf->download($fileName);
}
}
