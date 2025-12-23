<?php
// app/Http/Middleware/ValiderRetrait.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Frais\ValidationOperationService;

class ValiderRetrait
{
    protected $validationService;
    
    public function __construct(ValidationOperationService $validationService)
    {
        $this->validationService = $validationService;
    }
    
    public function handle(Request $request, Closure $next)
    {
        $compteId = $request->route('compte') ?? $request->input('compte_id');
        
        if (!$compteId) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non spécifié'
            ], 400);
        }
        
        $compte = \App\Models\compte\Compte::find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        $montant = $request->input('montant');
        
        if (!$montant || $montant <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Montant invalide'
            ], 400);
        }
        
        // Valider le retrait
        $validation = $this->validationService->validerRetrait($compte, $montant);
        
        if (!$validation['valide']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message']
            ], 400);
        }
        
        // Si validation requise pour retrait anticipé
        if (isset($validation['validation_requise']) && $validation['validation_requise']) {
            // Vérifier si l'utilisateur a les droits de validation
            if (!auth()->user()->hasPermission('valider_retrait_anticipe')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation requise pour ce retrait anticipé',
                    'validation_requise' => true
                ], 403);
            }
        }
        
        // Ajouter les informations de validation à la requête
        $request->merge([
            'validation_retrait' => $validation,
            'compte' => $compte
        ]);
        
        return $next($request);
    }
}