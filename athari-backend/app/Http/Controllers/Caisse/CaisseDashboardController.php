<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import explicite recommandé
use Exception;

class CaisseDashboardController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    public function index()
    {
        $user = auth()->user();

        // 1. Récupérer la session active avec une sécurité
        $session = DB::table('caisse_sessions')
            ->where('caissier_id', $user->id)
            ->where('statut', 'OU') // 'OU' pour OUVERT
            ->first();

        // Sécurité : Si pas de session, on s'arrête ici proprement
        if (!$session) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Aucune session de caisse ouverte.'
            ], 404);
        }

        // 2. Calculer les totaux
        // Correction : Utilisation du session_id si disponible ou filtrage par date
        $flux = DB::table('caisse_transactions')
            ->where('caissier_id', $user->id)
            // Idéalement, filtrez par session_id pour plus de précision :
            // ->where('session_id', $session->id) 
            ->whereDate('created_at', now()) 
            ->select('type_flux', 'type_versement', DB::raw('SUM(montant_brut) as total'))
            ->groupBy('type_flux', 'type_versement')
            ->get();

        // 3. Organiser les données (Correction de la ligne 67)
        // On utilise l'opérateur ?? 0 pour garantir qu'on a un chiffre même si la colonne est vide
        $soldeOuverture = (float)($session->solde_ouverture ?? 0);

        $bilan_especes = [
            'solde_ouverture' => $soldeOuverture,
            'total_entrees' => (float)$flux->where('type_versement', 'ESPECE')->whereIn('type_flux', ['VERSEMENT', 'ENTREE'])->sum('total'),
            'total_sorties' => (float)$flux->where('type_versement', 'ESPECE')->whereIn('type_flux', ['RETRAIT', 'SORTIE'])->sum('total'),
        ];
        
        $bilan_especes['net_a_justifier_physique'] = ($bilan_especes['solde_ouverture'] + $bilan_especes['total_entrees']) - $bilan_especes['total_sorties'];

        // 4. Retourner la réponse
        return response()->json([
            'statut' => 'success',
            'dashboard' => [
                'session' => [
                    'caisse' => 'Caisse N°' . ($session->caisse_id ?? 'Inconnue'),
                    'code' => $user->name,
                    'ouvert_le' => $session->created_at
                ],
                'bilan_especes' => $bilan_especes,
                'flux_digitaux' => $flux->where('type_versement', '!=', 'ESPECE')->groupBy('type_versement'),
                'validations_en_cours' => DB::table('caisse_demandes_validation')
                                            ->where('caissiere_id', $user->id)
                                            ->where('statut', 'EN_ATTENTE')->count()
            ]
        ]);
    }

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
}