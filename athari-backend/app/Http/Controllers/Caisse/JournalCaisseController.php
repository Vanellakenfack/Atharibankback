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

            // Génération du PDF
            $pdf = Pdf::loadView('pdf.journal_caisse', [
                'ouverture'    => $donnees['solde_ouverture'],
                'mouvements'   => $donnees['mouvements'],
                'total_debit'  => $donnees['total_debit'],
                'total_credit' => $donnees['total_credit'],
                'cloture'      => $donnees['solde_cloture'],
                'synthese'     => $donnees['mouvements']->groupBy('type_versement')->map(fn($items) => $items->sum('montant_debit')),
                'filtres'      => $filtres
            ])->setPaper('a4', 'landscape');

            return $pdf->download('journal_caisse.pdf');

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}