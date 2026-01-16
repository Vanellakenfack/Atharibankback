<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Exception;

class CaisseDashboardController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    /**
     * Récapitulatif des flux par type (ESPECE, OM, MOMO)
     * Utile pour la clôture ou le point de mi-journée
     */
    public function recapitulatifFlux($sessionId)
    {
        try {
            $recap = $this->caisseService->obtenirRecapitulatifCloture($sessionId);

            return response()->json([
                'statut' => 'success',
                'data' => $recap
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Vue globale du tableau de bord de la caissière
     */
 public function index()
{
    try {
        $user = auth()->user();
        
        // 1. Récupération de la session active
        $session = \DB::table('caisse_sessions as cs')
            ->join('caisses as c', 'cs.caisse_id', '=', 'c.id')
            ->select('cs.*', 'c.libelle_caisse', 'c.code_caisse')
            ->where('cs.caissier_id', $user->id)
            ->where('cs.statut', 'OU')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Aucune session active'], 404);
        }

        // 2. Récupération du récapitulatif des flux
        $recap = $this->caisseService->obtenirRecapitulatifCloture($session->id);

        // 3. Calcul des soldes théoriques
        $soldeEspeceInitial = (float)$session->solde_ouverture;
        $mouvementsEspece = $recap['ESPECE'] ?? collect([]);
        
        $totalEntrees = $mouvementsEspece->whereIn('type_flux', ['VERSEMENT', 'ENTREE'])->sum('total');
        $totalSorties = $mouvementsEspece->whereIn('type_flux', ['RETRAIT', 'SORTIE'])->sum('total');
        
        $soldeTheoriquePhysique = ($soldeEspeceInitial + $totalEntrees) - $totalSorties;

        // 4. Récupération des demandes de validation en attente
        $alertes = \DB::table('caisse_demandes_validation')
            ->where('caissiere_id', $user->id)
            ->where('statut', 'EN_ATTENTE')
            ->get();

        return response()->json([
            'statut' => 'success',
            'dashboard' => [
                'session' => [
                    'caisse' => $session->libelle_caisse,
                    'code' => $session->code_caisse,
                    'ouvert_le' => $session->created_at,
                ],
                'bilan_especes' => [
                    'solde_ouverture' => $soldeEspeceInitial,
                    'total_entrees' => $totalEntrees,
                    'total_sorties' => $totalSorties,
                    'net_a_justifier_physique' => $soldeTheoriquePhysique, // Ce qu'elle doit avoir en main
                ],
                'flux_digitaux' => $recap->except('ESPECE'), // OM, MOMO, etc.
                'validations_en_cours' => $alertes->count(),
                'liste_alertes' => $alertes
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}