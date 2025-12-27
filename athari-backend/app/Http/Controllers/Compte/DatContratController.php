<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use App\Models\Compte\ContratDat; 
use App\Models\Compte\DatType;
use App\Services\Compte\DATService;
use Illuminate\Http\Request;
use Exception;

class DatContratController extends Controller
{
    protected $datService;

    public function __construct(DATService $datService)
    {
        $this->datService = $datService;
    }

    /**
     * Liste tous les contrats pour le tableau React
     */
    public function index()
    {
        try {
            // Récupère les contrats avec les infos du compte
            $contracts = ContratDat::with('compte')->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'donnees' => $contracts // Harmonisé sur 'donnees' pour React
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * NOUVEAU : Simulation de gain (Résout l'erreur 405)
     */
    public function simulate(Request $request)
    {
        try {
            $request->validate([
                'montant' => 'required|numeric',
                'dat_type_id' => 'required|exists:dat_types,id'
            ]);

            $type = DatType::findOrFail($request->dat_type_id);
            $montant = $request->montant;

            // Calcul simulation : (Capital * Taux * (Mois/12))
            $gain_brut = ($montant * $type->taux_interet * $type->duree_mois) / 12;

            return response()->json([
                'statut' => 'success',
                'simulation' => [
                    'gain_net' => round($gain_brut, 0),
                    'total_echeance' => round($montant + $gain_brut, 0),
                    'date_fin' => now()->addMonths($type->duree_mois)->format('d/m/Y')
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Souscription à un nouveau contrat
     */
    public function store(Request $request)
    {
        try {
            $contrat = $this->datService->initialiserEtActiver(
                $request->account_id,
                $request->dat_type_id,
                $request->montant,
                $request->mode_versement ?? 'CAPITALISATION'
            );

            return response()->json([
                'success' => true,
                'statut' => 'success',
                'donnees' => $contrat
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Affiche les détails de calcul avant clôture (Simulation de sortie)
     */
    public function show($id)
    {
        try {
            $contrat = ContratDat::with('compte')->findOrFail($id);
            $simulation = $this->datService->calculerDetailsSortie($contrat);

            // Retourne les données formatées pour la modale de clôture React
            return response()->json([
                'success' => true,
                'donnees' => [
                    'capital' => $simulation['capital_actuel'],
                    'penalites' => $simulation['penalite'],
                    'montant_final' => $simulation['net_a_payer'],
                    'est_anticipe' => $simulation['est_anticipe']
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Contrat introuvable : ' . $e->getMessage()], 404);
        }
    }

    /**
     * Effectuer un versement supplémentaire (Tranches)
     */
    public function deposer(Request $request, $id)
    {
        try {
            $contrat = ContratDat::findOrFail($id);
            $montant = $request->input('montant');
            $duree = $request->input('duree_mois', 9); 

            $resultat = $this->datService->ajouterVersement($contrat, $montant, $duree);

            return response()->json([
                'statut' => 'success',
                'message' => 'Versement n°' . $resultat->nb_tranches_actuel . ' enregistré',
                'donnees' => $resultat
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Clôture définitive du contrat
     */
    public function cloturer($id)
    {
        try {
            $contrat = ContratDat::findOrFail($id);
            $details = $this->datService->cloturerContrat($contrat);

            return response()->json([
                'statut' => 'success',
                'message' => 'Contrat clôturé avec succès',
                'donnees' => $details
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}