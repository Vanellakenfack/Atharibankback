<?php
// app/Http/Controllers/FraisApplicationController.php

namespace App\Http\Controllers\frais;

use App\Http\Controllers\Controller;
use App\Models\frais\FraisApplication;
use App\Models\compte\Compte;
use App\Services\Frais\CalculFraisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FraisApplicationController extends Controller
{
    protected $calculFraisService;
    
    public function __construct(CalculFraisService $calculFraisService)
    {
        $this->calculFraisService = $calculFraisService;
    }
    
    /**
     * Afficher l'historique des frais appliqués
     */
    public function index(Request $request)
    {
        $query = FraisApplication::with(['compte', 'fraisCommission', 'validateur'])
            ->orderBy('date_application', 'desc');
        
        // Filtres
        if ($request->has('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }
        
        if ($request->has('type_frais')) {
            $query->where('type_frais', $request->type_frais);
        }
        
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_application', [
                Carbon::parse($request->date_debut),
                Carbon::parse($request->date_fin)
            ]);
        }
        
        $fraisApplications = $query->paginate(50);
        
        return response()->json([
            'success' => true,
            'data' => $fraisApplications
        ]);
    }
    
    /**
     * Appliquer les frais d'ouverture manuellement
     */
    public function appliquerFraisOuverture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|exists:comptes,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $compte = Compte::find($request->compte_id);
        
        try {
            $fraisApplication = $this->calculFraisService->appliquerFraisOuverture($compte);
            
            return response()->json([
                'success' => true,
                'message' => 'Frais d\'ouverture appliqués avec succès',
                'data' => $fraisApplication
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application des frais: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Lancer les commissions mensuelles
     */
    public function lancerCommissionsMensuelles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'confirmer' => 'required|boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        if (!$request->confirmer) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez confirmer l\'opération'
            ], 400);
        }
        
        try {
            $applications = $this->calculFraisService->appliquerCommissionsMensuelles($request->date);
            
            return response()->json([
                'success' => true,
                'message' => 'Commissions mensuelles appliquées avec succès',
                'data' => [
                    'nombre_applications' => count($applications),
                    'applications' => $applications
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application des commissions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Lancer les commissions SMS
     */
    public function lancerCommissionsSMS(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'confirmer' => 'required|boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        if (!$request->confirmer) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez confirmer l\'opération'
            ], 400);
        }
        
        try {
            $applications = $this->calculFraisService->appliquerCommissionsSMS($request->date);
            
            return response()->json([
                'success' => true,
                'message' => 'Commissions SMS appliquées avec succès',
                'data' => [
                    'nombre_applications' => count($applications),
                    'applications' => $applications
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application des commissions SMS: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculer les intérêts créditeurs
     */
    public function calculerInterets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'confirmer' => 'required|boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        if (!$request->confirmer) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez confirmer l\'opération'
            ], 400);
        }
        
        try {
            $applications = $this->calculFraisService->calculerInteretsCrediteurs($request->date);
            
            return response()->json([
                'success' => true,
                'message' => 'Intérêts créditeurs calculés avec succès',
                'data' => [
                    'nombre_applications' => count($applications),
                    'applications' => $applications
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des intérêts: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Valider une application de frais en attente
     */
    public function validerApplication(Request $request, $id)
    {
        $fraisApplication = FraisApplication::find($id);
        
        if (!$fraisApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Application non trouvée'
            ], 404);
        }
        
        if ($fraisApplication->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette application n\'est pas en attente de validation'
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:valider,rejeter',
            'motif' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            if ($request->action === 'valider') {
                // Appliquer les frais
                $compte = $fraisApplication->compte;
                
                if ($compte->solde >= $fraisApplication->montant) {
                    $compte->solde -= $fraisApplication->montant;
                    $compte->save();
                    
                    $fraisApplication->statut = 'applique';
                    $fraisApplication->valide_par = auth()->id();
                    $fraisApplication->valide_le = now();
                    $fraisApplication->description .= ' - Validé le ' . now()->format('d/m/Y');
                    
                    $message = 'Frais validés et appliqués avec succès';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solde insuffisant pour appliquer les frais'
                    ], 400);
                }
            } else {
                $fraisApplication->statut = 'annule';
                $fraisApplication->description .= ' - Rejeté le ' . now()->format('d/m/Y') . ': ' . $request->motif;
                
                $message = 'Frais rejetés avec succès';
            }
            
            $fraisApplication->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $fraisApplication
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir les frais en attente pour un compte
     */
    public function getEnAttente($compteId)
    {
        $fraisEnAttente = FraisApplication::with('fraisCommission')
            ->where('compte_id', $compteId)
            ->where('statut', 'en_attente')
            ->orderBy('date_application', 'desc')
            ->get();
            
        $totalEnAttente = $fraisEnAttente->sum('montant');
        
        return response()->json([
            'success' => true,
            'data' => [
                'frais_en_attente' => $fraisEnAttente,
                'total_en_attente' => $totalEnAttente,
                'nombre_en_attente' => $fraisEnAttente->count()
            ]
        ]);
    }
}