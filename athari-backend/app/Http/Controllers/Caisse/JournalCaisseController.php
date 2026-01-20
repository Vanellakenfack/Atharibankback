<?php
namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class JournalCaisseController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

<<<<<<< HEAD
=======
    /**
     * Récupère le journal de caisse
     */
>>>>>>> cb64e432d5a995c59abfc5f8c879a8cdccac1f1b
    public function obtenirJournal(Request $request)
    {
        try {
            // Validation des paramètres
            $request->validate([
                'caisse_id'   => 'required|exists:caisses,id',
                'code_agence' => 'required|string',
                'date_debut'  => 'required|date',
                'date_fin'    => 'required|date|after_or_equal:date_debut',
            ]);

            $filtres = $request->only(['caisse_id', 'code_agence', 'date_debut', 'date_fin']);
            
            // Récupération des données via le service
            $donnees = $this->caisseService->obtenirJournalCaisseComplet($filtres);

            return response()->json([
                'statut' => 'success',
                'solde_ouverture' => $donnees['solde_ouverture'],
                'mouvements' => $donnees['mouvements'],
                'total_debit' => $donnees['total_debit'],
                'total_credit' => $donnees['total_credit'],
                'solde_cloture' => $donnees['solde_cloture'],
                'synthese' => $donnees['mouvements']->groupBy('type_versement')
                    ->map(fn($items) => [
                        'debit' => $items->sum('montant_debit'),
                        'credit' => $items->sum('montant_credit'),
                        'count' => $items->count()
                    ])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

<<<<<<< HEAD
=======
    /**
     * Exporte le journal de caisse en PDF
     */
>>>>>>> cb64e432d5a995c59abfc5f8c879a8cdccac1f1b
    public function exportPdf(Request $request)
    {
        // Validation des filtres pour Postman
        $request->validate([
            'caisse_id'   => 'required|exists:caisses,id',
            'code_agence' => 'required|string',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
        ]);

        $caisse = \DB::table('caisses')->where('id', $request->caisse_id)->first();

        try {
            $filtres = $request->all();
            
            // Récupération des données via le service
            $donnees = $this->caisseService->obtenirJournalCaisseComplet($filtres);

            // Calcul de la synthèse par type de versement
            $synthese = [];
            if (isset($donnees['mouvements'])) {
                $grouped = $donnees['mouvements']->groupBy('type_versement');
                foreach ($grouped as $type => $items) {
                    $synthese[$type] = [
                        'debit' => $items->sum('montant_debit'),
                        'credit' => $items->sum('montant_credit'),
                        'count' => $items->count()
                    ];
                }
            }

            // Génération du PDF
            $pdf = Pdf::loadView('pdf.journal_caisse', [
                'ouverture'    => $donnees['solde_ouverture'],
                'mouvements'   => $donnees['mouvements'],
                'total_debit'  => $donnees['total_debit'],
                'total_credit' => $donnees['total_credit'],
                'cloture'      => $donnees['solde_cloture'],
                'synthese'     => $donnees['mouvements']->groupBy('type_versement')->map(fn($items) => $items->sum('montant_debit')),
                'filtres'      => $filtres,
                'code_caisse'  => $caisse->code_caisse ?? 'N/A'
                'synthese'     => $synthese,
                'filtres'      => $filtres
            ])->setPaper('a4', 'landscape');

            return $pdf->download('journal_caisse.pdf');

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}