<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditApplication;
use App\Models\CreditProduct; // Ajouté si nécessaire
use App\Services\CreditCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class CreditApplicationController extends Controller
{
    protected CreditCalculationService $calculator;

    public function __construct(CreditCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

     /**
     * Récupérer toutes les demandes de crédit
     */
    public function index(Request $request)
    {
        try {
            // Log pour débogage
            \Log::info('Récupération des demandes de crédit');
            
            // Vérifier si la table existe
            if (!\Schema::hasTable('credit_applications')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table non trouvée'
                ], 500);
            }

            // Construction de la requête
            $query = CreditApplication::query();
            
            // Filtrer par statut si fourni
            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }
            
            // Filtrer par client si fourni
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }
            
            // Filtrer par agent si fourni
            if ($request->has('agent_id')) {
                $query->where('created_by', $request->agent_id);
            }

            // CORRECTION : Charger les relations de manière sécurisée
            // Charger d'abord la relation client (probablement présente)
            $query->with(['client']);
            
            // Essayer de charger la relation product si elle existe
            try {
                // Vérifier si le modèle a une méthode product()
                $testApp = new CreditApplication();
                if (method_exists($testApp, 'product') || method_exists($testApp, 'creditProduct')) {
                    // Essayer product d'abord, sinon creditProduct
                    if (method_exists($testApp, 'product')) {
                        $query->with(['product']);
                    } else {
                        $query->with(['creditProduct']);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Relation product non disponible: ' . $e->getMessage());
            }

            // Pagination ou récupération complète
            if ($request->has('per_page')) {
                $applications = $query->latest()->paginate($request->per_page);
            } else {
                $applications = $query->latest()->get();
            }

            // Compter les statuts pour dashboard
            $stats = [
                'total' => CreditApplication::count(),
                'soumis' => CreditApplication::where('statut', 'SOUMIS')->count(),
                'en_cours' => CreditApplication::where('statut', 'EN_COURS')->count(),
                'approuve' => CreditApplication::where('statut', 'APPROUVE')->count(),
                'rejete' => CreditApplication::where('statut', 'REJETE')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'count' => $applications->count(),
                'stats' => $stats,
                'data' => $applications
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur dans index(): ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des données',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Créer une demande de crédit (Agent)
     */
    public function store(Request $request)
    {
        // 1. Validation des données
        $validator = Validator::make($request->all(), [
            'client_id'          => 'required|exists:clients,id',
            'client_name'        => 'required|string|max:255',
            'credit_product_id'  => 'exists:credit_products,id',
            'type_credit'        => 'required|string|max:255',
            'montant'            => 'required|numeric|min:0',
            'duree'              => 'required|integer|min:1',
            'taux_interet'       => 'required|numeric',
            'source_revenus'     => 'required|string|max:255',
            'revenus_mensuels'   => 'required|numeric',
            // Validation des fichiers
            'demande_credit'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_identite'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'plan_epargne'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'photos_4x4'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'plan_localisation'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'facture_electricite'=> 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'casier_judiciaire'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'historique_compte'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Préparation des données
        $data = $request->except([
            'demande_credit', 'plan_epargne', 'document_identite', 
            'photos_4x4', 'plan_localisation', 'facture_electricite', 
            'casier_judiciaire', 'historique_compte'
        ]);

        // Génération de la référence
        $reference = 'CR-' . strtoupper(Str::random(8));
        $data['reference']   = $reference;
        $data['num_dossier'] = $reference;
        
        // Statut par défaut
        $data['statut'] = 'SOUMIS';
        
        // Ajout de l'utilisateur créateur
        $data['created_by'] = auth()->id() ?? 1;

        // 3. Gestion automatique des fichiers
        $documents = [
            'demande_credit', 'plan_epargne', 'document_identite', 
            'photos_4x4', 'plan_localisation', 'facture_electricite', 
            'casier_judiciaire', 'historique_compte'
        ];

        foreach ($documents as $docName) {
            if ($request->hasFile($docName)) {
                $file = $request->file($docName);
                $fileName = $docName . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs(
                    'uploads/credits/' . $request->client_id, 
                    $fileName,
                    'public'
                );
                $data[$docName] = $path;
            }
        }

        // 4. Création en base de données avec gestion d'erreur améliorée
        try {
            $credit = CreditApplication::create($data);
            
            // CORRECTION : Chargement sécurisé avec vérification
            // Charger seulement le client pour commencer
            $credit->load('client');
            
            // Essayer de charger le produit uniquement si la relation existe
            try {
                if (method_exists($credit, 'product')) {
                    $credit->load('product');
                }
            } catch (\Exception $e) {
                \Log::warning('Impossible de charger la relation product: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Demande de crédit enregistrée avec succès',
                'data'    => $credit
            ], 201);

        } catch (\Exception $e) {
            // Log détaillé pour débogage
            \Log::error('Erreur création crédit: ' . $e->getMessage());
            \Log::error('Fichier: ' . $e->getFile());
            \Log::error('Ligne: ' . $e->getLine());
            \Log::error('Données: ' . json_encode($data));
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Erreur lors de l\'enregistrement',
                'debug'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function stats()
{
    $stats = [
        'total' => CreditApplication::count(),
        'pending' => CreditApplication::where('statut', 'pending')->count(),
        'approved' => CreditApplication::where('statut', 'approved')->orWhere('statut', 'approuve')->count(),
        'rejected' => CreditApplication::where('statut', 'rejected')->orWhere('statut', 'rejete')->count(),
        'submitted' => CreditApplication::where('statut', 'submitted')->orWhere('statut', 'soumis')->count(),
    ];
    
    return response()->json($stats);
}

public function dossiersChefAgence()
{
    return response()->json(
        CreditApplication::with([
            'client',
            'avis.user'
        ])
        ->where('statut', 'EN_ATTENTE_CHEF_AGENCE')
        ->orderBy('created_at', 'desc')
        ->get()
    );
}

    /**
     * Détails d'un dossier
     */
    public function show($id)
    {
        // CORRECTION : Chargement sécurisé des relations
        $credit = CreditApplication::with(['client'])->findOrFail($id);
        
        // Ajouter la relation product si elle existe
        if (method_exists($credit, 'product')) {
            $credit->load('product');
        }

        return response()->json($credit);
    }

    // Ajoutez cette méthode dans votre CreditApplicationController

/**
 * Simulation pour crédit flash (endpoint unifié)
 */
public function simulateCredit(Request $request)
{
    $validated = $request->validate([
        'montant' => 'required|numeric|min:1',
        'product_code' => 'required|string'
    ]);
    
    try {
        $product = CreditProduct::where('code', $validated['product_code'])->first();
        
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit de crédit non trouvé'
            ], 404);
        }
        
        // Si c'est un crédit flash, utiliser la logique spécifique
        if ($product->code === 'FLASH_24H') {
            $simulation = $product->calculateFlashInterests($validated['montant']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Simulation Flash 24H',
                'data' => array_merge($simulation, [
                    'produit_nom' => $product->nom,
                    'produit_code' => $product->code,
                    'penalite_retard' => $product->penalite_retard . '%',
                    'temps_obtention' => $product->temps_obtention . 'h'
                ])
            ]);
        }
        
        // Pour les autres produits
        $interets = $product->calculerInterets($validated['montant']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Simulation crédit',
            'data' => [
                'montant_capital' => $validated['montant'],
                'interets_totaux' => $interets,
                'total_a_rembourser' => $validated['montant'] + $interets,
                'taux_interet' => $product->taux_interet,
                'duree_jours' => $product->duree_max,
                'produit_nom' => $product->nom,
                'produit_code' => $product->code
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur simulation: ' . $e->getMessage()
        ], 500);
    }
}
}