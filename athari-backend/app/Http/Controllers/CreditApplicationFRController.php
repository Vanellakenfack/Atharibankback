<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditType;
use App\Models\client\Client;
use App\Models\Compte\Compte;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; // Ajout de l'import manquant

class CreditApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Affiche la liste des demandes de crédit
     */
    public function index(Request $request)
    {
        try {
            $query = CreditApplication::query();

            if ($request->has('compte_id')) {
                $query->where('compte_id', $request->compte_id);
            }

            if ($request->has('credit_type_id')) {
                $query->where('credit_type_id', $request->credit_type_id);
            }

            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            $demandes = $query->with(['avis'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $demandes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in index method: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcule les frais d'étude selon les paliers du Flash 24H
     */
    private function calculateFraisEtudeFlash24($montant)
    {
        if ($montant <= 25000) {
            return 500;
        } elseif ($montant <= 50000) {
            return 1000;
        } elseif ($montant <= 250000) {
            return 2000;
        } elseif ($montant <= 500000) {
            return 3000;
        } elseif ($montant <= 2000000) {
            return ceil($montant * 3000 / 500000);
        }
        return 0;
    }

    /**
     * Calcule les intérêts selon les paliers du Flash 24H
     */
    private function calculateInteretFlash24($montant, $duree)
    {
        if ($duree < 1) $duree = 1;
        
        if ($montant <= 25000) {
            $premier_jour = 1500;
            $journalier = 500;
        } elseif ($montant <= 50000) {
            $premier_jour = 2000;
            $journalier = 500;
        } elseif ($montant <= 250000) {
            $premier_jour = 5000;
            $journalier = 1000;
        } elseif ($montant <= 500000) {
            $premier_jour = 10000;
            $journalier = 1000;
        } elseif ($montant <= 2000000) {
            $premier_jour = ceil($montant * 10000 / 500000);
            $journalier = 1000;
        } else {
            $premier_jour = 0;
            $journalier = 0;
        }
        
        return $premier_jour + ($journalier * ($duree - 1));
    }

    /**
     * Calcule la pénalité par jour selon les paliers du Flash 24H
     */
    private function calculatePenaliteFlash24($montant)
    {
        if ($montant <= 25000) {
            return 500;
        } elseif ($montant <= 50000) {
            return 1000;
        } elseif ($montant <= 250000) {
            return 2000;
        } elseif ($montant <= 500000) {
            return 3000;
        } elseif ($montant <= 2000000) {
            return ceil($montant * 3000 / 500000);
        }
        return 0;
    }

    /**
     * Crée une nouvelle demande de crédit
     */
    public function store(Request $request)
    {
        try {
            \Log::info('=== NOUVELLE DEMANDE DE CRÉDIT ===');
            \Log::info('Données reçues:', $request->all());
            
            // Validation complète
           // Remplacer la validation complète par :
$validator = Validator::make($request->all(), [
    'compte_id' => 'required|exists:comptes,id',
    'credit_type_id' => 'required|exists:credit_types,id',
    'montant' => 'required|numeric|min:1',
    'duree' => 'required|integer|min:1',
    'source_revenus' => 'required|string|max:255',
    'revenus_mensuels' => 'required|numeric|min:0',
    'autres_revenus' => 'nullable|numeric|min:0',
    'montant_dettes' => 'nullable|numeric|min:0',
    'description_dette' => 'nullable|string|max:500',
    'nom_banque' => 'nullable|string|max:100',
    'numero_banque' => 'nullable|string|max:50',
    'numero_personne_contact' => 'required|string|max:20',
    'urgence' => 'nullable|in:normale,urgente,tres_urgente',
    'garantie' => 'nullable|string|max:255',
    'plan_epargne' => 'nullable|boolean',
    'observation' => 'nullable|string|max:1000',
    'description_domicile' => 'nullable|string|max:1000', // Changé en string
    'description_activite' => 'nullable|string|max:1000', // Changé en string
    
    // Documents validation (uniquement les fichiers)
    'demande_credit_img' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
    'photocopie_cni' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'photo_4x4' => 'required|file|mimes:jpg,jpeg,png|max:2048',
    'plan_localisation' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'facture_electricite' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'casier_judiciaire' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'historique_compte' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'geolocalisation_img' => 'required|file|mimes:jpg,jpeg,png|max:5120',
    'plan_localisation_activite_img' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'photo_activite_img' => 'required|file|mimes:jpg,jpeg,png|max:2048',
    'plan_localisation_domicile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    'geolocalisation_domicile' => 'required|file|mimes:jpg,jpeg,png|max:5120',
    'photo_domicile_1' => 'required|file|mimes:jpg,jpeg,png|max:2048',
    'photo_domicile_2' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
    'photo_domicile_3' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
    'photo_activite_1' => 'required|file|mimes:jpg,jpeg,png|max:2048',
    'photo_activite_2' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
    'photo_activite_3' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
    'lettre_non_remboursement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
]);

            if ($validator->fails()) {
                \Log::error('Validation errors:', $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!auth()->check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non authentifié'
                ], 401);
            }

            // Récupération des données
            $compte = Compte::find($request->compte_id);
            if (!$compte) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Compte non trouvé'
                ], 404);
            }

            $creditType = DB::table('credit_types')->find($request->credit_type_id);
            if (!$creditType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Type de crédit non trouvé'
                ], 404);
            }

            // Stockage des fichiers
            $documents = [];
            $fileFields = [
                'demande_credit_img',
                'photocopie_cni',
                'photo_4x4',
                'plan_localisation',
                'facture_electricite',
                'casier_judiciaire',
                'historique_compte',
                'geolocalisation_img',
                'plan_localisation_activite_img',
                'photo_activite_img',
                'plan_localisation_domicile',
                'description_domicile',
                'geolocalisation_domicile',
                'photo_domicile_1',
                'photo_domicile_2',
                'photo_domicile_3',
                'description_activite',
                'photo_activite_1',
                'photo_activite_2',
                'photo_activite_3',
                'lettre_non_remboursement',
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('credit-applications/documents', $filename, 'public');
                    $documents[$field] = $path;
                    \Log::info("Fichier $field sauvegardé: $path");
                }
            }

            // Calculs financiers
            $calculs = $this->calculateCreditDetails(
                $creditType,
                $request->montant,
                $request->duree
            );

            // Données FINALES pour la base
            $data = [
                'numero_demande' => 'CD-' . date('Ymd') . '-' . strtoupper(uniqid()),
                'user_id' => auth()->id(),
                'client_id' => $compte->client_id,
                'compte_id' => $request->compte_id,
                'credit_type_id' => $request->credit_type_id,
                'montant' => $request->montant,
                'duree' => $request->duree,
                'taux_interet' => $calculs['taux_interet'],
                'interet_total' => $calculs['interet_total'],
                'frais_dossier' => $calculs['frais_dossier'],
                'frais_etude' => $calculs['frais_etude'],
                'montant_total' => $calculs['montant_total'],
                'penalite_par_jour' => $calculs['penalite_par_jour'],
                'calcul_details' => json_encode($calculs['details']),
                'date_demande' => now(),
                'source_revenus' => $request->source_revenus,
                'revenus_mensuels' => $request->revenus_mensuels,
                'autres_revenus' => $request->input('autres_revenus', '0.00'),
                'montant_dettes' => $request->input('montant_dettes', '0.00'),
                'description_dette' => $request->input('description_dette', 'RAS'),
                'nom_banque' => $request->input('nom_banque', 'N/A'),
                'numero_banque' => $request->input('numero_banque', 'N/A'),
                'numero_personne_contact' => $request->input('numero_personne_contact', 'N/A'),
                'observation' => $request->input('observation', 'RAS'),
                'urgence' => $request->input('urgence', 'normale'),
                'garantie' => $request->input('garantie', ''),
                'plan_epargne' => $request->boolean('plan_epargne', false),
                'statut' => 'SOUMIS',
            ];

            // Ajouter les documents
            $data = array_merge($data, $documents);

            \Log::info('Données à insérer:', array_keys($data));
            
            // CRÉATION FINALE
            $creditApplication = CreditApplication::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Demande créée avec succès!',
                'data' => $creditApplication,
                'calculs' => $calculs
            ]);

        } catch (\Exception $e) {
            \Log::error('ERREUR CRITIQUE:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage(),
                'debug' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Calcule les détails du crédit
     */
    private function calculateCreditDetails($creditType, $montant, $duree)
    {
        if (($creditType->code ?? '') === 'FLASH_24H') {
            $frais = $this->calculateFraisEtudeFlash24($montant);
            $interet = $this->calculateInteretFlash24($montant, $duree);
            $penalite = $this->calculatePenaliteFlash24($montant);
            
            return [
                'taux_interet' => 0,
                'interet_total' => $interet,
                'frais_dossier' => $frais,
                'frais_etude' => $frais,
                'montant_total' => $montant + $frais + $interet,
                'penalite_par_jour' => $penalite,
                'details' => [
                    'type' => 'flash_24h',
                    'palier' => $this->getPalierFlash24($montant)
                ]
            ];
        }
        
        // Pour les autres crédits
        $tauxInteret = $creditType->taux_interet ?? 0;
        $interetTotal = $montant * ($tauxInteret / 100) * ($duree / 365);
        $fraisDossier = $creditType->frais_dossier ?? 0;
        
        return [
            'taux_interet' => $tauxInteret,
            'interet_total' => $interetTotal,
            'frais_dossier' => $fraisDossier,
            'frais_etude' => $fraisDossier,
            'montant_total' => $montant + $fraisDossier + $interetTotal,
            'penalite_par_jour' => $creditType->penalite ?? 0,
            'details' => ['type' => 'standard']
        ];
    }

    /**
     * Détermine le palier pour Flash 24H
     */
    private function getPalierFlash24($montant)
    {
        if ($montant <= 25000) return 1;
        if ($montant <= 50000) return 2;
        if ($montant <= 250000) return 3;
        if ($montant <= 500000) return 4;
        if ($montant <= 2000000) return 5;
        return 0;
    }

    /**
     * Affiche les détails d'une demande
     */
    public function show($id)
    {
        try {
            $demande = CreditApplication::with(['avis', 'compte', 'creditType', 'user'])->find($id);

            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            // Add file URLs
            $fileFields = [
                'demande_credit_img',
                'photocopie_cni',
                'photo_4x4',
                'plan_localisation',
                'facture_electricite',
                'casier_judiciaire',
                'historique_compte',
                'geolocalisation_img',
                'plan_localisation_activite_img',
                'photo_activite_img',
                'plan_localisation_domicile',
                'description_domicile',
                'geolocalisation_domicile',
                'photo_domicile_1',
                'photo_domicile_2',
                'photo_domicile_3',
                'description_activite',
                'photo_activite_1',
                'photo_activite_2',
                'photo_activite_3',
                'lettre_non_remboursement',
            ];

            foreach ($fileFields as $field) {
                if ($demande->{$field}) {
                    $demande->{$field . '_url'} = Storage::url($demande->{$field});
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $demande
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la demande de crédit: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une demande
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $demande = CreditApplication::find($id);
            
            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }
            
            $request->validate([
                'status' => 'required|in:SOUMIS,EN_COURS,CA_VALIDE,ASC_VALIDE,COMITE_VALIDE,APPROUVE,REJETE',
                'comment' => 'nullable|string',
            ]);

            $demande->update([
                'statut' => $request->status,
                'observation' => $request->comment ?: $demande->observation
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Statut mis à jour avec succès',
                'data' => $demande
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in updateStatus: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les demandes par rôle
     */
    public function getByRole(Request $request, $role)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $query = CreditApplication::query();
            
            // Filtrer selon le rôle demandé
            switch ($role) {
                case 'CA':
                    $query->where('statut', 'SOUMIS');
                    break;
                case 'ASC':
                    $query->where('statut', 'CA_VALIDE');
                    break;
                case 'COMITE':
                    $query->where(function($q) {
                        $q->where('statut', 'ASC_VALIDE')
                          ->orWhere('statut', 'COMITE_VALIDE');
                    });
                    break;
                case 'AC':
                    // Agent de crédit - toutes les applications
                    break;
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Rôle non valide'
                    ], 400);
            }

            $applications = $query->with(['avis'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Load additional information
            if ($applications->isNotEmpty()) {
                $compteIds = $applications->pluck('compte_id')->unique()->filter();
                $creditTypeIds = $applications->pluck('credit_type_id')->unique()->filter();
                
                if ($compteIds->isNotEmpty()) {
                    $comptes = DB::table('comptes')
                        ->whereIn('id', $compteIds)
                        ->select('id', 'numero_compte', 'client_id')
                        ->get()
                        ->keyBy('id');
                    
                    $applications->each(function($app) use ($comptes) {
                        $app->compte_info = $comptes[$app->compte_id] ?? null;
                    });
                }
                
                if ($creditTypeIds->isNotEmpty()) {
                    $creditTypes = DB::table('credit_types')
                        ->whereIn('id', $creditTypeIds)
                        ->select('id', 'credit_characteristics', 'code', 'description')
                        ->get()
                        ->keyBy('id');
                    
                    $applications->each(function($app) use ($creditTypes) {
                        $app->credit_type_info = $creditTypes[$app->credit_type_id] ?? null;
                    });
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $applications
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getByRole: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les applications par statut
     */
    public function getByStatus(Request $request, $status)
    {
        try {
            $query = CreditApplication::where('statut', $status);
            
            if ($request->has('compte_id')) {
                $query->where('compte_id', $request->compte_id);
            }

            if ($request->has('credit_type_id')) {
                $query->where('credit_type_id', $request->credit_type_id);
            }

            $applications = $query->with(['avis'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $applications
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getByStatus: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une demande
     */
    public function update(Request $request, $id)
    {
        try {
            $demande = CreditApplication::find($id);
            
            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }
            
            $request->validate([
                'statut' => 'sometimes|in:SOUMIS,EN_ETUDE,APPROUVE,REJETE,ACCEPTE,REFUSE,ANNULE',
                'observation' => 'nullable|string',
            ]);

            $demande->update($request->only(['statut', 'observation']));

            return response()->json([
                'status' => 'success',
                'message' => 'Demande mise à jour avec succès',
                'data' => $demande
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soumet un avis sur une demande de crédit
     */
    public function submitAvis(Request $request, $id)
    {
        try {
            $demande = CreditApplication::find($id);
            
            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }
            
            $request->validate([
                'role' => 'required|in:chef-agence,analyste,directeur',
                'decision' => 'required|in:approved,rejected',
                'commentaire' => 'nullable|string',
            ]);

            $newStatut = '';
            
            switch ($request->role) {
                case 'chef-agence':
                    $newStatut = $request->decision === 'approved' ? 'EN_COURS' : 'REJETE';
                    break;
                case 'analyste':
                    $newStatut = $request->decision === 'approved' ? 'EN_COURS' : 'REJETE';
                    break;
                case 'directeur':
                    $newStatut = $request->decision === 'approved' ? 'APPROUVE' : 'REJETE';
                    break;
            }

            $demande->update([
                'statut' => $newStatut,
                'observation' => $request->commentaire ?: $demande->observation
            ]);

            // Create avis record if you have the AvisCredit model
            // \App\Models\Credit\AvisCredit::create([
            //     'credit_application_id' => $demande->id,
            //     'user_id' => auth()->id(),
            //     'role' => $request->role,
            //     'decision' => $request->decision,
            //     'commentaire' => $request->commentaire,
            // ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Avis soumis avec succès',
                'data' => $demande
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in submitAvis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la soumission de l\'avis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les demandes pour le chef d'agence
     */
    public function forChefAgence(Request $request)
    {
        try {
            $query = CreditApplication::whereIn('statut', ['SOUMIS', 'EN_COURS']);
            
            if ($request->has('compte_id')) {
                $query->where('compte_id', $request->compte_id);
            }

            if ($request->has('credit_type_id')) {
                $query->where('credit_type_id', $request->credit_type_id);
            }

            $demandes = $query->with(['avis'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Load additional information
            if ($demandes->isNotEmpty()) {
                $compteIds = $demandes->pluck('compte_id')->unique()->filter();
                $creditTypeIds = $demandes->pluck('credit_type_id')->unique()->filter();
                
                $comptes = DB::table('comptes')
                    ->whereIn('id', $compteIds)
                    ->select('id', 'numero_compte', 'client_id')
                    ->get()
                    ->keyBy('id');
                
                $creditTypes = DB::table('credit_types')
                    ->whereIn('id', $creditTypeIds)
                    ->select('id', 'credit_characteristics', 'code', 'description')
                    ->get()
                    ->keyBy('id');
                
                $demandes->each(function($demande) use ($comptes, $creditTypes) {
                    $demande->compte_info = $comptes[$demande->compte_id] ?? null;
                    $demande->credit_type_info = $creditTypes[$demande->credit_type_id] ?? null;
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => $demandes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in forChefAgence: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une demande avec ses avis
     */
    public function showWithReviews($id)
    {
        try {
            // On récupère le dossier AVEC ses avis
            $application = CreditApplication::with('reviews.user')->find($id);

            if (!$application) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            // Format the response
            $response = [
                'dossier' => $application->numero_demande,
                'statut' => $application->statut,
                'montant' => $application->montant,
                'duree' => $application->duree,
                'avis_du_comite' => $application->reviews->map(function ($review) {
                    return [
                        'intervenant' => $review->role_at_vote ?? $review->user->name ?? 'Inconnu',
                        'decision' => $review->decision,
                        'commentaire' => $review->commentaires,
                        'date' => $review->created_at ? $review->created_at->format('d/m/Y H:i') : 'N/A'
                    ];
                })
            ];

            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in showWithReviews: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}