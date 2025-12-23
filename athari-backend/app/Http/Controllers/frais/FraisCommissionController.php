<?php

namespace App\Http\Controllers\frais;

use App\Http\Controllers\Controller;
use App\Models\frais\FraisCommission;
use App\Models\compte\TypeCompte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FraisCommissionController extends Controller
{
    /**
     * Afficher la liste des configurations de frais
     */
    public function index()
    {
        $fraisCommissions = FraisCommission::with('typeCompte')
            ->orderBy('type_compte_id')
            ->paginate(20);
            
        return response()->json([
            'success' => true,
            'data' => $fraisCommissions
        ]);
    }
    
    /**
     * Afficher une configuration spécifique
     */
    public function show($id)
    {
        $fraisCommission = FraisCommission::with('typeCompte')->find($id);
        
        if (!$fraisCommission) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $fraisCommission
        ]);
    }
    
    /**
     * Créer une nouvelle configuration
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_compte_id' => 'required|exists:types_comptes,id',
            'frais_ouverture' => 'nullable|numeric|min:0',
            'frais_tenue_compte' => 'nullable|numeric|min:0',
            'commission_mouvement' => 'nullable|numeric|min:0',
            'commission_retrait' => 'nullable|numeric|min:0',
            'commission_sms' => 'nullable|numeric|min:0',
            'taux_interet_annuel' => 'nullable|numeric|min:0|max:100',
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',
            'minimum_compte' => 'nullable|numeric|min:0',
            'seuil_commission_mensuelle' => 'nullable|numeric|min:0',
            'commission_mensuelle_elevee' => 'nullable|numeric|min:0',
            'commission_mensuelle_basse' => 'nullable|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Vérifier si une configuration existe déjà pour ce type de compte
        $existing = FraisCommission::where('type_compte_id', $request->type_compte_id)->first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Une configuration existe déjà pour ce type de compte'
            ], 409);
        }
        
        $fraisCommission = FraisCommission::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Configuration créée avec succès',
            'data' => $fraisCommission->load('typeCompte')
        ], 201);
    }
    
    /**
     * Mettre à jour une configuration
     */
    public function update(Request $request, $id)
    {
        $fraisCommission = FraisCommission::find($id);
        
        if (!$fraisCommission) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration non trouvée'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'frais_ouverture' => 'nullable|numeric|min:0',
            'frais_tenue_compte' => 'nullable|numeric|min:0',
            'commission_mouvement' => 'nullable|numeric|min:0',
            'commission_retrait' => 'nullable|numeric|min:0',
            'commission_sms' => 'nullable|numeric|min:0',
            'taux_interet_annuel' => 'nullable|numeric|min:0|max:100',
            'penalite_retrait_anticipe' => 'nullable|numeric|min:0|max:100',
            'minimum_compte' => 'nullable|numeric|min:0',
            'seuil_commission_mensuelle' => 'nullable|numeric|min:0',
            'commission_mensuelle_elevee' => 'nullable|numeric|min:0',
            'commission_mensuelle_basse' => 'nullable|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $fraisCommission->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Configuration mise à jour avec succès',
            'data' => $fraisCommission->load('typeCompte')
        ]);
    }
    
    /**
     * Supprimer une configuration
     */
    public function destroy($id)
    {
        $fraisCommission = FraisCommission::find($id);
        
        if (!$fraisCommission) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration non trouvée'
            ], 404);
        }
        
        $fraisCommission->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Configuration supprimée avec succès'
        ]);
    }
    
    /**
     * Obtenir les frais pour un type de compte
     */
    public function getByTypeCompte($typeCompteId)
    {
        $fraisCommission = FraisCommission::with('typeCompte')
            ->where('type_compte_id', $typeCompteId)
            ->first();
            
        if (!$fraisCommission) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration non trouvée pour ce type de compte'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $fraisCommission
        ]);
    }
    
    /**
     * Simuler les frais pour une opération
     */
    public function simulerFrais(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_compte_id' => 'required|exists:types_comptes,id',
            'type_operation' => 'required|in:ouverture,retrait,versement,cloture',
            'montant' => 'required|numeric|min:0',
            'est_anticipe' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $fraisConfig = FraisCommission::where('type_compte_id', $request->type_compte_id)->first();
        
        if (!$fraisConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration des frais non trouvée'
            ], 404);
        }
        
        $simulation = [
            'type_operation' => $request->type_operation,
            'montant_operation' => $request->montant,
            'frais_applicables' => []
        ];
        
        switch ($request->type_operation) {
            case 'ouverture':
                if ($fraisConfig->frais_ouverture_actif) {
                    $simulation['frais_applicables'][] = [
                        'type' => 'frais_ouverture',
                        'montant' => $fraisConfig->frais_ouverture,
                        'description' => 'Frais d\'ouverture de compte'
                    ];
                }
                break;
                
            case 'retrait':
                if ($fraisConfig->commission_retrait_actif) {
                    $simulation['frais_applicables'][] = [
                        'type' => 'commission_retrait',
                        'montant' => $fraisConfig->commission_retrait,
                        'description' => 'Commission sur retrait'
                    ];
                }
                
                if ($request->est_anticipe && $fraisConfig->penalite_actif) {
                    $penalite = $request->montant * ($fraisConfig->penalite_retrait_anticipe / 100);
                    $simulation['frais_applicables'][] = [
                        'type' => 'penalite_retrait_anticipe',
                        'montant' => $penalite,
                        'taux' => $fraisConfig->penalite_retrait_anticipe . '%',
                        'description' => 'Pénalité de retrait anticipé'
                    ];
                }
                break;
        }
        
        // Calculer le total
        $totalFrais = array_sum(array_column($simulation['frais_applicables'], 'montant'));
        $simulation['total_frais'] = $totalFrais;
        $simulation['montant_net'] = $request->type_operation === 'retrait' 
            ? $request->montant - $totalFrais
            : $request->montant;
        
        return response()->json([
            'success' => true,
            'data' => $simulation
        ]);
    }
}