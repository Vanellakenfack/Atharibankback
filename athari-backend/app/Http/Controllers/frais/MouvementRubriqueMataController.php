<?php

namespace App\Http\Controllers\frais;

use App\Http\Controllers\Controller;
use App\Models\frais\MouvementRubriqueMata;
use App\Models\compte\Compte;
use App\Services\Frais\GestionRubriqueMataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MouvementRubriqueMataController extends Controller
{
    protected $gestionRubriqueMataService;
    
    public function __construct(GestionRubriqueMataService $gestionRubriqueMataService)
    {
        $this->gestionRubriqueMataService = $gestionRubriqueMataService;
    }
    
    /**
     * Afficher l'historique des mouvements d'un compte MATA
     */
    public function index(Request $request, $compteId)
    {
        $compte = Compte::find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        // Vérifier que le compte est MATA
        if (!$compte->typeCompte->est_mata) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'est pas un compte MATA'
            ], 400);
        }
        
        $query = MouvementRubriqueMata::where('compte_id', $compteId)
            ->orderBy('created_at', 'desc');
        
        // Filtres
        if ($request->has('rubrique')) {
            $query->where('rubrique', $request->rubrique);
        }
        
        if ($request->has('type_mouvement')) {
            $query->where('type_mouvement', $request->type_mouvement);
        }
        
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('created_at', [
                $request->date_debut,
                $request->date_fin
            ]);
        }
        
        $mouvements = $query->paginate($request->get('per_page', 50));
        
        return response()->json([
            'success' => true,
            'data' => [
                'compte' => $compte->only(['id', 'numero_compte', 'solde']),
                'mouvements' => $mouvements
            ]
        ]);
    }
    
    /**
     * Afficher le récapitulatif des rubriques
     */
    public function recapitulatif($compteId)
    {
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        // Vérifier que le compte est MATA
        if (!$compte->typeCompte->est_mata) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'est pas un compte MATA'
            ], 400);
        }
        
        $recapitulatif = $this->gestionRubriqueMataService->getRecapitulatifRubriques($compte);
        
        return response()->json([
            'success' => true,
            'data' => $recapitulatif
        ]);
    }
    
    /**
     * Enregistrer un versement sur une rubrique
     */
    public function versement(Request $request, $compteId)
    {
        $validator = Validator::make($request->all(), [
            'rubrique' => 'required|in:SANTÉ,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITÉ',
            'montant' => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        try {
            $mouvement = $this->gestionRubriqueMataService->enregistrerVersement(
                $compte,
                $request->rubrique,
                $request->montant,
                $request->reference
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Versement enregistré avec succès',
                'data' => [
                    'mouvement' => $mouvement,
                    'nouveau_solde_rubrique' => $mouvement->solde_rubrique,
                    'nouveau_solde_global' => $mouvement->solde_global
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du versement: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Enregistrer un retrait sur une rubrique
     */
    public function retrait(Request $request, $compteId)
    {
        $validator = Validator::make($request->all(), [
            'rubrique' => 'required|in:SANTÉ,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITÉ',
            'montant' => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        try {
            $mouvement = $this->gestionRubriqueMataService->enregistrerRetrait(
                $compte,
                $request->rubrique,
                $request->montant,
                $request->reference
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Retrait enregistré avec succès',
                'data' => [
                    'mouvement' => $mouvement,
                    'nouveau_solde_rubrique' => $mouvement->solde_rubrique,
                    'nouveau_solde_global' => $mouvement->solde_global
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Transférer des fonds entre rubriques
     */
    public function transferer(Request $request, $compteId)
    {
        $validator = Validator::make($request->all(), [
            'rubrique_source' => 'required|in:SANTÉ,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITÉ',
            'rubrique_destination' => 'required|in:SANTÉ,BUSINESS,FETE,FOURNITURE,IMMO,SCOLARITÉ',
            'montant' => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        if ($request->rubrique_source === $request->rubrique_destination) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de transférer vers la même rubrique'
            ], 400);
        }
        
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        try {
            $resultat = $this->gestionRubriqueMataService->transfererEntreRubriques(
                $compte,
                $request->rubrique_source,
                $request->rubrique_destination,
                $request->montant,
                $request->reference
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Transfert effectué avec succès',
                'data' => $resultat
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du transfert: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Répartir un montant sur toutes les rubriques
     */
    public function repartir(Request $request, $compteId)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:1',
            'type_operation' => 'required|in:versement,retrait',
            'reference' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        try {
            $mouvements = $this->gestionRubriqueMataService->repartirMontantSurRubriques(
                $compte,
                $request->montant,
                $request->type_operation,
                $request->reference
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Répartition effectuée avec succès',
                'data' => [
                    'nombre_mouvements' => count($mouvements),
                    'mouvements' => $mouvements,
                    'nouveau_solde_global' => $compte->fresh()->solde
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la répartition: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir l'historique d'une rubrique spécifique
     */
    public function historiqueRubrique($compteId, $rubrique)
    {
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        // Vérifier que le compte est MATA
        if (!$compte->typeCompte->est_mata) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'est pas un compte MATA'
            ], 400);
        }
        
        // Vérifier que la rubrique existe
        $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
        if (!in_array($rubrique, $rubriques)) {
            return response()->json([
                'success' => false,
                'message' => 'Rubrique non configurée pour ce compte'
            ], 400);
        }
        
        $historique = $this->gestionRubriqueMataService->getHistoriqueRubrique($compte, $rubrique);
        $soldeActuel = MouvementRubriqueMata::getSoldeRubrique($compteId, $rubrique);
        
        return response()->json([
            'success' => true,
            'data' => [
                'compte' => $compte->only(['id', 'numero_compte']),
                'rubrique' => $rubrique,
                'solde_actuel' => $soldeActuel,
                'historique' => $historique
            ]
        ]);
    }
    
    /**
     * Obtenir le solde d'une rubrique spécifique
     */
    public function soldeRubrique($compteId, $rubrique)
    {
        $compte = Compte::with('typeCompte')->find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }
        
        // Vérifier que le compte est MATA
        if (!$compte->typeCompte->est_mata) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'est pas un compte MATA'
            ], 400);
        }
        
        $solde = MouvementRubriqueMata::getSoldeRubrique($compteId, $rubrique);
        $dernierMouvement = MouvementRubriqueMata::getDernierMouvement($compteId, $rubrique);
        
        return response()->json([
            'success' => true,
            'data' => [
                'compte_id' => $compteId,
                'numero_compte' => $compte->numero_compte,
                'rubrique' => $rubrique,
                'solde' => $solde,
                'dernier_mouvement' => $dernierMouvement,
                'solde_global_compte' => $compte->solde
            ]
        ]);
    }
}