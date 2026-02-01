<?php

namespace App\Http\Controllers;

use App\Models\CreditApplication;
use App\Models\User;
use App\Models\Client; // Added missing import
use App\Models\CreditType; // Added missing import
use App\Notifications\CreditApplicationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CreditProduct;

class CreditApplicationController extends Controller
{

public function index()
{
    try {
        // Correction : on utilise 'product' car c'est le nom de la fonction dans ton modèle
        $applications = CreditApplication::with(['client', 'product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $applications->count(),
            'data'    => $applications
        ], 200);

    } catch (\Exception $e) {
        // En cas d'erreur 500, ce message te dira exactement quelle colonne manque
        return response()->json([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage()
        ], 500);
    }
}

public function reviewAAR(Request $request, $id)
{
    // Vérifiez les règles de validation
    $validated = $request->validate([
        // Quels champs sont attendus ?
        'decision' => 'required|in:approved,rejected',
        'comment' => 'required|string|min:10',
        // Peut-être d'autres champs ?
        'status' => 'sometimes|required',
        'user_id' => 'sometimes|required',
    ]);
    
    // Logique du contrôleur
}

    /**
     * Store a newly created credit application.
     */
public function store(Request $request)
{
    $validated = $request->validate([
        // Infos Crédit
        'client_id'         => 'required|exists:clients,id',
        'type_credit'       => 'required|string',
        'montant_demande'   => 'required|numeric|min:1',
        'duree_mois'        => 'required|integer|min:1',
        'taux_interet'      => 'required|numeric|min:0',
        'observation'       => 'nullable|string',
        'credit_product_id' => 'required|exists:credit_products,id',

        // Infos Financières
        'source_revenus'      => 'required|string',
        'revenus_mensuels'    => 'required|numeric|min:0',
        'autres_revenus'      => 'required|numeric|min:0',
        'depenses_mensuelles' => 'required|numeric|min:0',
        'montant_dettes'      => 'required|numeric|min:0',
        'description_dettes'  => 'required|string',
        'nom_banque'          => 'required|string',
        'numero_compte'       => 'required|string',

        // Documents
        'demande_credit'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'plan_epargne'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'document_identite'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'photos_4x4'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'plan_localisation'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'facture_electricite' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'casier_judiciaire'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'historique_compte'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    ]);

    try {
        DB::beginTransaction();

        // Récupérer le produit de crédit
        $creditProduct = CreditProduct::find($validated['credit_product_id']);

        if (!$creditProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit de crédit invalide.'
            ], 422);
        }

        // Calculer les frais d'étude dynamiques
        $fraisEtude = $creditProduct->calculFraisEtude($validated['montant_demande']);

        // Créer la demande
        $application = CreditApplication::create([
            'client_id'           => $validated['client_id'],
            'agent_credit_id'     => auth()->id() ?? 1,
            'credit_type_id'      => $creditProduct->id,
            'type_credit_label'   => $validated['type_credit'],
            'montant_demande'     => $validated['montant_demande'],
            'duree_mois'          => $validated['duree_mois'],
            'taux_interet'        => $validated['taux_interet'],
            'frais_etude'         => $fraisEtude, // ✅ frais calculés dynamiquement
            'source_revenus'      => $validated['source_revenus'],
            'revenus_mensuels'    => $validated['revenus_mensuels'],
            'autres_revenus'      => $validated['autres_revenus'],
            'depenses_mensuelles' => $validated['depenses_mensuelles'],
            'montant_dettes'      => $validated['montant_dettes'],
            'description_dettes'  => $validated['description_dettes'],
            'nom_banque'          => $validated['nom_banque'],
            'numero_compte'       => $validated['numero_compte'],
            'observation'         => $validated['observation'],
            'status'              => 'SOUMIS',
            'submitted_at'        => now(),
        ]);

        // Stocker les fichiers
        $files = [
            'demande_credit', 'plan_epargne', 'document_identite', 
            'photos_4x4', 'plan_localisation', 'facture_electricite', 
            'casier_judiciaire', 'historique_compte'
        ];

        foreach ($files as $key) {
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('credits/' . $application->id, 'public');
                $application->$key = $path;
            }
        }

        $application->save();

        DB::commit();

        // Ajouter le frais d'étude dans la réponse JSON
        $response = $application->load(['client', 'product']);
        $response->frais_etude_calculé = $fraisEtude;

        return response()->json([
            'status' => 'success',
            'message' => 'Demande de crédit enregistrée avec succès',
            'data' => $response
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}



    /**
     * Alternative method for saving as draft
     */
    public function saveAsDraft(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'credit_type_id' => 'nullable|exists:credit_types,id',
            'montant_demande' => 'nullable|numeric|min:0',
            'duree_mois' => 'nullable|integer|min:1',
            'taux_interet' => 'nullable|numeric|min:0|max:100',
            'observation' => 'nullable|string|max:1000',
        ]);

        try {
            $application = CreditApplication::create(array_merge($validated, [
                'agent_credit_id' => auth()->id(),
                'status' => 'brouillon',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Brouillon sauvegardé avec succès',
                'data' => $application
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde brouillon: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde du brouillon'
            ], 500);
        }
    }

    /**
 * Display the specified credit application.
 */
public function show($id)
{
    try {
        // On récupère la demande avec ses relations définies dans le modèle
        $application = CreditApplication::with(['client', 'creditType'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $application
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Demande de crédit non trouvée.'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération : ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Submit an existing draft application
     */
    public function submitDraft($id)
    {
        try {
            DB::beginTransaction();

            $application = CreditApplication::where('agent_credit_id', auth()->id())
                ->where('status', 'brouillon')
                ->findOrFail($id);

            $application->update([
                'status' => 'soumis',
                'submitted_at' => now(),
            ]);

            // Add history
            if (method_exists($application, 'histories')) {
                $application->histories()->create([
                    'user_id' => auth()->id(),
                    'action' => 'status_change',
                    'ancien_statut' => 'brouillon',
                    'nouveau_statut' => 'soumis',
                    'observations' => 'Brouillon soumis pour analyse',
                ]);
            }

            // Notify chef d'agence
            $chefAgence = User::role('chef_agence')->first();
            if ($chefAgence) {
                $chefAgence->notify(new CreditApplicationSubmitted($application));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Brouillon soumis avec succès',
                'data' => $application->load(['client', 'creditType'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission du brouillon'
            ], 500);
        }
    }
}