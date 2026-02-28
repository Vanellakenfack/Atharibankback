<?php

namespace App\Http\Controllers\Comptabilite\Balance;

use App\Http\Controllers\Controller;
use App\Services\Comptabilite\Balance\BalanceService;
use App\Exports\Comptabilite\Balance\BalanceAuxiliaireExport;
use App\Exports\Comptabilite\Balance\BalanceGeneraleExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    // =========================================================================
    // --- SECTION : BALANCE GÉNÉRALE
    // =========================================================================

    /**
     * Récupération JSON de la Balance Générale
     */
    public function getBalanceGenerale(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
            'agence_id'  => 'nullable|exists:agences,id'
        ]);

        try {
            $data = $this->balanceService->getBalanceGenerale(
                $request->date_debut,
                $request->date_fin,
                $request->agence_id
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur Balance Générale : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export EXCEL Balance Générale
     */
    public function exporterExcelGenerale(Request $request)
    {
        $data = $this->balanceService->getBalanceGenerale(
            $request->date_debut,
            $request->date_fin,
            $request->agence_id
        );
        
        if (empty($data['donnees'])) {
            return back()->with('error', 'Aucun mouvement trouvé pour cette période.');
        }

        $nomFichier = 'Balance_Generale_' . $request->date_debut . '.xlsx';

        return Excel::download(
            new BalanceGeneraleExport($data, $request->date_debut, $request->date_fin), 
            $nomFichier
        );
    }

    /**
     * Export PDF Balance Générale
     */
    public function exporterPdfGenerale(Request $request)
    {

        $data = $this->balanceService->getBalanceGenerale(
            $request->date_debut,
            $request->date_fin,
            $request->agence_id
        );

        \Log::info('EXPORT PDF BALANCE GENERALE DATA', $data);

        $pdf = Pdf::loadView('reports.balance.balance_generale', [
            'donnees'    => $data['donnees'],
            'stats'      => $data['statistiques'],
            'dateDebut'  => $request->date_debut,
            'dateFin'    => $request->date_fin,
            'agence_nom' => $data['agence_nom'] ?? 'TOUTES LES AGENCES'
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Balance_Generale_' . now()->format('d_m_Y') . '.pdf');
    }


    // =========================================================================
    // --- SECTION : BALANCE AUXILIAIRE (Détail Clients/Tiers)
    // =========================================================================

    /**
     * Récupération JSON de la Balance Auxiliaire
     */
    public function getBalanceAuxiliaire(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
            'agence_id'  => 'nullable|exists:agences,id'
        ]);

        try {
            $data = $this->balanceService->getBalanceAuxiliaire(
                $request->date_debut,
                $request->date_fin,
                $request->agence_id
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur Balance Auxiliaire : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export EXCEL Balance Auxiliaire
     */
    public function exporterExcelAuxiliaire(Request $request)
    {

        // Conversion code agence → id si besoin
        $agenceId = $request->agence_id;
        if ($agenceId && !is_numeric($agenceId)) {
            $agenceId = \App\Models\Agency::where('code', $agenceId)->value('id');
        }

        $chapitres = $this->balanceService->getBalanceAuxiliaire(
            $request->date_debut,
            $request->date_fin,
            $agenceId
        );
        
        $nomFichier = 'Balance_Auxiliaire_' . $request->date_debut . '.xlsx';

        return Excel::download(
            new BalanceAuxiliaireExport($chapitres, $request->date_debut, $request->date_fin), 
            $nomFichier
        );
    }

    /**
     * Export PDF Balance Auxiliaire
     */
   public function exporterPdfAuxiliaire(Request $request)
{
    // 1. Conversion agence si nécessaire
    $agenceId = $request->agence_id;
    if ($agenceId && !is_numeric($agenceId)) {
        $agenceId = \App\Models\Agency::where('code', $agenceId)->value('id');
    }

    // 2. Récupération des données (Le service renvoie la collection groupée directement)
    $donnees = $this->balanceService->getBalanceAuxiliaire(
        $request->date_debut,
        $request->date_fin,
        $agenceId
    );

    // 3. Calcul des statistiques globales (Puisque le service ne les renvoie pas dans cette méthode)
    $stats = [
        'total_general_debit_report'  => $donnees->sum('total_report_debit'),
        'total_general_credit_report' => $donnees->sum('total_report_credit'),
        'total_general_debit_periode' => $donnees->sum('total_debit'),
        'total_general_credit_periode'=> $donnees->sum('total_credit'),
        'total_general_debit'         => $donnees->sum('total_solde_debit'),
        'total_general_credit'        => $donnees->sum('total_solde_credit'),
    ];

    // 4. Génération du PDF
    $pdf = Pdf::loadView('reports.balance.balance_auxiliaire', [
        'donnees'    => $donnees, // La collection groupée
        'stats'      => $stats,
        'dateDebut'  => $request->date_debut,
        'dateFin'    => $request->date_fin,
        'agence_nom' => $request->agence_nom ?? 'TOUTES LES AGENCES'
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Balance_Auxiliaire_' . now()->format('d_m_Y') . '.pdf');
}
}