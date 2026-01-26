<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class CaisseDashboardController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    public function index()
    {
        try {
            $user = auth()->user();

            // 1. Récupérer la session active
            $session = DB::table('caisse_sessions')
                ->where('caissier_id', $user->id)
                ->where('statut', 'OU')
                ->first();

            if (!$session) {
                return response()->json([
                    'statut' => 'error',
                    'message' => 'Aucune session de caisse ouverte.'
                ], 404);
            }

            // 2. Calculer les flux de la SESSION ACTUELLE (Indépendant de la date brute)
            $flux = DB::table('caisse_transactions')
                ->where('session_id', $session->id)
                ->select(
                    'type_flux', 
                    'type_versement', 
                    DB::raw('SUM(montant_brut) as total_montant'),
                    DB::raw('COUNT(*) as nombre_ops')
                )
                ->groupBy('type_flux', 'type_versement')
                ->get();

            // 3. RÉCUPÉRER LES TRANSACTIONS RÉCENTES
            // On utilise 'reference_unique' selon votre migration
            $transactions = DB::table('caisse_transactions as t')
                ->leftJoin('comptes as c', 't.compte_id', '=', 'c.id')
                ->where('t.session_id', $session->id)
                ->select(
                    't.id', 
                    't.reference_unique as ref', 
                    't.type_flux', 
                    't.type_versement', 
                    't.montant_brut', 
                    't.created_at',
                    't.compte_id',
                    't.statut',
                    'c.est_en_opposition' // On récupère l'info d'opposition
                )
                ->orderBy('t.created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($t) {
                    return [
                        'id' => $t->id,
                        'ref' => $t->ref,
                        'type' => $t->type_flux,
                        'mode' => $t->type_versement,
                        'montant' => (float)$t->montant_brut,
                        'heure' => Carbon::parse($t->created_at)->format('H:i'),
                        'compte_id' => $t->compte_id,
                        'statut' => $t->statut,
                        'is_opposition' => (bool)$t->est_en_opposition,
                        'color' => in_array($t->type_flux, ['VERSEMENT', 'ENTREE']) ? 'success' : 'error'
                    ];
                });

            // 4. Bilan Espèces (Coffre physique)
            $soldeOuverture = (float)($session->solde_ouverture ?? 0);
            $entreesEspeces = (float)$flux->where('type_versement', 'ESPECE')
                                         ->whereIn('type_flux', ['VERSEMENT', 'ENTREE'])
                                         ->sum('total_montant');
            $sortiesEspeces = (float)$flux->where('type_versement', 'ESPECE')
                                         ->whereIn('type_flux', ['RETRAIT', 'SORTIE'])
                                         ->sum('total_montant');

            $soldeTheorique = ($soldeOuverture + $entreesEspeces) - $sortiesEspeces;

            // 5. Préparation des données pour le graphique (Activité horaire)
            $graphique = DB::table('caisse_transactions')
                ->where('session_id', $session->id)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%H:00') as heure"),
                    DB::raw("SUM(CASE WHEN type_flux IN ('VERSEMENT', 'ENTREE') THEN montant_brut ELSE 0 END) as entrees"),
                    DB::raw("SUM(CASE WHEN type_flux IN ('RETRAIT', 'SORTIE') THEN montant_brut ELSE 0 END) as sorties")
                )
                ->groupBy('heure')
                ->orderBy('heure', 'asc')
                ->get();

            // 6. Réponse finale harmonisée pour le frontend
            return response()->json([
                'statut' => 'success',
                'dashboard' => [
                    'session' => [
                        'caisse' => 'Caisse N°' . ($session->caisse_id ?? '---'),
                        'code' => $user->name,
                        'ouvert_le' => $session->created_at,
                        'duree' => Carbon::parse($session->created_at)->diffForHumans()
                    ],
                    'bilan_especes' => [
                        'solde_ouverture' => $soldeOuverture,
                        'total_entrees' => $entreesEspeces,
                        'total_sorties' => $sortiesEspeces,
                        'net_a_justifier_physique' => $soldeTheorique
                    ],
                    'flux_digitaux' => $this->formaterFluxDigitaux($flux),
                    'transactions_recentes' => $transactions,
                    'graphique' => $graphique,
                    'validations_en_cours' => DB::table('caisse_demandes_validation')
                        ->where('caissiere_id', $user->id)
                        ->where('statut', 'EN_ATTENTE')
                        ->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur lors de la récupération : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formate les flux OM/MTN pour le frontend
     */
    private function formaterFluxDigitaux($flux)
    {
        return $flux->where('type_versement', '!=', 'ESPECE')
            ->groupBy('type_versement')
            ->map(function ($items, $key) {
                return [
                    'mode' => $key,
                    'entrees' => (float)$items->whereIn('type_flux', ['VERSEMENT', 'ENTREE'])->sum('total_montant'),
                    'sorties' => (float)$items->whereIn('type_flux', ['RETRAIT', 'SORTIE'])->sum('total_montant'),
                    'nb_ops' => $items->sum('nombre_ops')
                ];
            })->values();
    }
}