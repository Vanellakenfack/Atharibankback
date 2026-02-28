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
            // On récupère l'utilisateur actuel (facultatif pour la session, mais utile pour le nom)
            $user = auth()->user();

            // 1. RÉCUPÉRATION DE LA SESSION : On cherche simplement la session ouverte la plus récente
            // Cela évite de bloquer si l'auth est instable ou si on veut voir la session en cours
            $session = DB::table('caisse_sessions')
                ->where('statut', 'OU')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$session) {
                return response()->json([
                    'statut' => 'error',
                    'message' => 'Aucune session de caisse ouverte sur le système.'
                ], 404);
            }

            // 2. CALCUL DES FLUX (Basé sur l'ID de la session trouvée)
            $flux = DB::table('caisse_transactions')
                ->where('session_id', $session->id)
                ->where('statut', 'VALIDE')
                ->select(
                    'type_flux', 
                    'type_versement', 
                    DB::raw('SUM(montant_brut) as total_montant'),
                    DB::raw('COUNT(*) as nombre_ops')
                )
                ->groupBy('type_flux', 'type_versement')
                ->get();

            // 3. TRANSACTIONS RÉCENTES
            $transactions = DB::table('caisse_transactions as t')
                ->leftJoin('comptes as c', 't.compte_id', '=', 'c.id')
                ->where('t.session_id', $session->id)
                ->where('t.statut', 'VALIDE')
                ->select(
                    't.id', 
                    't.reference_unique as ref', 
                    't.type_flux', 
                    't.type_versement', 
                    't.montant_brut', 
                    't.created_at',
                    't.compte_id',
                    't.statut',
                    'c.est_en_opposition'
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

            // 4. BILAN ESPÈCES
            $soldeOuverture = (float)($session->solde_ouverture ?? 0);
            $entreesEspeces = (float)$flux->where('type_versement', 'ESPECE')
                                         ->whereIn('type_flux', ['VERSEMENT', 'ENTREE'])
                                         ->sum('total_montant');
            $sortiesEspeces = (float)$flux->where('type_versement', 'ESPECE')
                                         ->whereIn('type_flux', ['RETRAIT', 'SORTIE'])
                                         ->sum('total_montant');

            $soldeTheorique = ($soldeOuverture + $entreesEspeces) - $sortiesEspeces;

            // 5. GRAPHIQUE HORAIRE
            $graphique = DB::table('caisse_transactions')
                ->where('session_id', $session->id)
                ->where('statut', 'VALIDE')
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%H:00') as heure"),
                    DB::raw("SUM(CASE WHEN type_flux IN ('VERSEMENT', 'ENTREE') THEN montant_brut ELSE 0 END) as entrees"),
                    DB::raw("SUM(CASE WHEN type_flux IN ('RETRAIT', 'SORTIE') THEN montant_brut ELSE 0 END) as sorties")
                )
                ->groupBy('heure')
                ->orderBy('heure', 'asc')
                ->get();

            // 6. RÉPONSE FINALE HARMONISÉE
            return response()->json([
                'statut' => 'success',
                'dashboard' => [
                    'session' => [
                        'caisse' => 'Caisse N°' . ($session->caisse_id ?? '---'),
                        'code' => $user ? $user->name : 'Système', // Sécurisé si user null
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
                        ->where('statut', 'EN_ATTENTE')
                        // On ne filtre par user que si nécessaire, sinon on compte tout pour la session
                        ->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur technique : ' . $e->getMessage()
            ], 500);
        }
    }

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