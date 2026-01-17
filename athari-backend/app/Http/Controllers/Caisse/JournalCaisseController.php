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

    /**
     * Récupère le journal de caisse
     */
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

    /**
     * Exporte le journal de caisse en PDF
     */
    public function exportPdf(Request $request)
    {
        // Validation des filtres pour Postman
        $request->validate([
            'caisse_id'   => 'required|exists:caisses,id',
            'code_agence' => 'required|string',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
        ]);

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
                'synthese'     => $synthese,
                'filtres'      => $filtres
            ])->setPaper('a4', 'landscape');

            return $pdf->download('journal_caisse.pdf');

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}