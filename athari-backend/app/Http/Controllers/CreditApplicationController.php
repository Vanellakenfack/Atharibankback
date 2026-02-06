<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditType;
use App\Models\client\Client;
use App\Models\Compte\Compte;
use Illuminate\Support\Facades\DB;

class CreditApplicationController extends Controller
{
    /**
     * Affiche la liste des demandes de crédit
     */
    public function index(Request $request)
    {
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

        // Chargez d'abord les demandes sans les relations problématiques
        $demandes = $query->with([
            'avis'
        ])->orderBy('created_at', 'desc')->get();

        // Chargez les comptes séparément pour éviter les erreurs de colonnes
        if ($demandes->isNotEmpty()) {
            $compteIds = $demandes->pluck('compte_id')->unique()->filter();
            
            if ($compteIds->isNotEmpty()) {
                $comptes = DB::table('comptes')
                    ->whereIn('id', $compteIds)
                    ->select('id', 'numero_compte', 'client_id')
                    ->get()
                    ->keyBy('id');
                
                $demandes->each(function($demande) use ($comptes) {
                    $demande->compte_info = $comptes[$demande->compte_id] ?? null;
                });
            }
        }

        // Chargez les types de crédit séparément
        if ($demandes->isNotEmpty()) {
            $creditTypeIds = $demandes->pluck('credit_type_id')->unique()->filter();
            
            if ($creditTypeIds->isNotEmpty()) {
                $creditTypes = DB::table('credit_types')
                    ->whereIn('id', $creditTypeIds)
                    ->select('id', 'credit_characteristics', 'code', 'description') // CORRECTION ICI : 'nom' remplacé par 'description'
                    ->get()
                    ->keyBy('id');
                
                $demandes->each(function($demande) use ($creditTypes) {
                    $demande->credit_type_info = $creditTypes[$demande->credit_type_id] ?? null;
                });
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $demandes
        ]);
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
        
        // Déterminer le palier
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
        $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'credit_type_id' => 'required|exists:credit_types,id',
            'montant' => 'required|numeric|min:1',
            'duree' => 'required|integer|min:1|max:240',
            'source_revenus' => 'required|string|max:255',
            'revenus_mensuels' => 'required|numeric|min:0',
            'observation' => 'nullable|string',
        ]);

        // Vérifiez si le compte existe
        $compte = Compte::find($request->compte_id);
        if (!$compte) {
            return response()->json([
                'status' => 'error',
                'message' => 'Compte non trouvé'
            ], 404);
        }

        // Récupérez les données du type de crédit directement depuis la table
        $creditTypeData = DB::table('credit_types')->find($request->credit_type_id);
        if (!$creditTypeData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Type de crédit non trouvé'
            ], 404);
        }

        // Initialisation des variables de calcul
        $frais_etude = 0;
        $interet_total = 0;
        $penalite_par_jour = 0;
        $taux_interet = $creditTypeData->taux_interet ?? 0;
        $calcul_details = [];

        // Calcul spécifique pour Flash 24H
        if (($creditTypeData->code ?? '') === 'FLASH_24H') {
            $frais_etude = $this->calculateFraisEtudeFlash24($request->montant);
            $interet_total = $this->calculateInteretFlash24($request->montant, $request->duree);
            $penalite_par_jour = $this->calculatePenaliteFlash24($request->montant);
            $taux_interet = 0; // Pas de taux en pourcentage pour Flash 24H
            
            $calcul_details = [
                'type' => 'flash_24h',
                'palier' => $this->getPalierFlash24($request->montant),
                'formule_interet' => 'premier_jour + (journalier × (duree-1))',
                'frais_etude_fixe' => $frais_etude,
                'interet_calcule' => $interet_total,
                'penalite_journaliere' => $penalite_par_jour
            ];
        } else {
            // Pour les autres types de crédit
            $frais_etude = $creditTypeData->frais_dossier ?? 0;
            
            // Calcul des intérêts (simplifié)
            if ($taux_interet > 0) {
                $interet_total = $request->montant * $taux_interet / 100 * ($request->duree / 12);
            }
            
            $penalite_par_jour = $creditTypeData->penalite ?? 0;
            
            $calcul_details = [
                'type' => 'standard',
                'taux_interet_annuel' => $taux_interet,
                'frais_dossier_pourcentage' => $creditTypeData->frais_dossier ?? 0,
                'penalite_pourcentage' => $creditTypeData->penalite ?? 0
            ];
        }

        // Documents du client (récupérez depuis le compte si disponibles)
        $documents = [
            'photo_4x4' => $compte->photo_4x4 ?? null,
            'plan_localisation' => $compte->plan_localisation ?? null,
            'facture_electricite' => $compte->facture_electricite ?? null,
            'casier_judiciaire' => $compte->casier_judiciaire ?? null,
            'historique_compte' => $compte->historique_compte ?? null,
        ];

        // Numéro de demande unique
        $numero_demande = 'CD-' . date('Ymd') . '-' . strtoupper(uniqid());

        // Montant total à rembourser
        $montant_total = $request->montant + $frais_etude + $interet_total;

        // Création de la demande
        $creditApplication = CreditApplication::create([
            'numero_demande' => $numero_demande,
            'compte_id' => $compte->id,
            'credit_type_id' => $creditTypeData->id,
            'montant' => $request->montant,
            'duree' => $request->duree,
            'taux_interet' => $taux_interet,
            'interet_total' => $interet_total,
            'frais_dossier' => $frais_etude,
            'frais_etude' => $frais_etude,
            'montant_total' => $montant_total,
            'penalite_par_jour' => $penalite_par_jour,
            'calcul_details' => $calcul_details,
            'date_demande' => now(),
            'source_revenus' => $request->source_revenus,
            'revenus_mensuels' => $request->revenus_mensuels,
            'autres_revenus' => $request->autres_revenus ?? null,
            'montant_dettes' => $request->montant_dettes ?? null,
            'description_dette' => $request->description_dette ?? null,
            'nom_banque' => $request->nom_banque ?? null,
            'numero_banque' => $request->numero_banque ?? null,
            'plan_epargne' => $request->boolean('plan_epargne', false),
            'photo_4x4' => $documents['photo_4x4'],
            'plan_localisation' => $documents['plan_localisation'],
            'facture_electricite' => $documents['facture_electricite'],
            'casier_judiciaire' => $documents['casier_judiciaire'],
            'historique_compte' => $documents['historique_compte'],
            'observation' => $request->observation ?? null,
            'statut' => 'SOUMIS'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Demande de crédit créée avec succès',
            'data' => $creditApplication,
            'resume_calcul' => [
                'montant_emprunte' => $request->montant,
                'frais_etude' => $frais_etude,
                'interet_total' => $interet_total,
                'penalite_par_jour_retard' => $penalite_par_jour,
                'montant_total_rembourser' => $montant_total,
                'mensualite_estimee' => $request->duree > 0 ? round($montant_total / $request->duree, 2) : 0
            ]
        ]);
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
            $demande = CreditApplication::with(['avis'])->find($id);

            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            // Chargez les informations supplémentaires de manière sécurisée
            $demande->compte_info = DB::table('comptes')
                ->where('id', $demande->compte_id)
                ->select('id', 'numero_compte', 'client_id')
                ->first();

            $demande->credit_type_info = DB::table('credit_types')
                ->where('id', $demande->credit_type_id)
                ->select('id', 'credit_characteristics', 'code', 'description')
                ->first();

            // Chargez les informations du client si nécessaire
            if ($demande->compte_info && $demande->compte_info->client_id) {
                $client = Client::with(['physique', 'morale'])->find($demande->compte_info->client_id);
                if ($client) {
                    $demande->client_info = [
                        'id' => $client->id,
                        'nom_complet' => $client->nom_complet,
                        'type_client' => $client->type_client,
                        'num_client' => $client->num_client
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $demande
            ]);
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            \Log::error('Erreur lors de la récupération de la demande de crédit: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }


    /**
 * Mettre à jour le statut d'une demande
 */
public function updateStatus(Request $request, $id)
{
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
}

public function getByRole(Request $request, $role)
{
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

    $applications = $query->with(['avis', 'client_info', 'credit_type_info'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $applications
    ]);
}

/**
 * Récupérer les applications par statut
 */
public function getByStatus(Request $request, $status)
{
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
}
    /**
     * Met à jour une demande
     */
    public function update(Request $request, $id)
    {
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
    }

    /**
     * Soumet un avis sur une demande de crédit (pour chef d'agence)
     */
    public function submitAvis(Request $request, $id)
    {
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

        // Vous devrez créer un modèle AvisCredit pour stocker les avis
        // Pour l'instant, on va simplement mettre à jour le statut
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

        // Mettez à jour le statut
        $demande->update([
            'statut' => $newStatut,
            'observation' => $request->commentaire ?: $demande->observation
        ]);

        // Ici, vous devriez créer un enregistrement dans la table avis_credits
        // DB::table('avis_credits')->insert([
        //     'credit_application_id' => $demande->id,
        //     'role' => $request->role,
        //     'decision' => $request->decision,
        //     'commentaire' => $request->commentaire,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Avis soumis avec succès',
            'data' => $demande
        ]);
    }

    /**
     * Liste les demandes pour le chef d'agence (statuts spécifiques)
     */
    public function forChefAgence(Request $request)
    {
        $query = CreditApplication::whereIn('statut', ['SOUMIS', 'EN_COURS']);
        
        if ($request->has('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }

        if ($request->has('credit_type_id')) {
            $query->where('credit_type_id', $request->credit_type_id);
        }

        // Chargez les demandes
        $demandes = $query->with(['avis'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Chargez les informations supplémentaires
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
                ->select('id', 'credit_characteristics', 'code', 'description') // CORRECTION ICI : 'nom' remplacé par 'description'
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
    }
}