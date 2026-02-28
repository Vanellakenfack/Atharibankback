<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalCaisseController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    /**
     * Récupère le journal de caisse via JSON (pour l'affichage Vue/React)
     */
    public function obtenirJournal(Request $request)
{
    try {
        $request->validate([
            'caisse_id'   => 'required|exists:caisses,id',
            'code_agence' => 'required|string',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
        ]);

        $donnees = $this->caisseService->obtenirJournalCaisseComplet($request->all());

        // On récupère journal_groupe (qui contient VERSEMENTS et RETRAITS)
        // et on fusionne tout en une seule collection pour le traitement JSON
        $tousLesMouvements = collect($donnees['journal_groupe'])->flatten();

        $groupes = $tousLesMouvements->groupBy('type_versement')->map(function ($items, $type) {
            return [
                'type' => $type,
                'total_entree' => $items->sum('montant_debit'),
                'total_sortie' => $items->sum('montant_credit'),
                'operations' => $items->map(fn($m) => [
                    'date' => $m->date_mouvement,
                    'ref' => $m->reference_operation,
                    'compte'  => $m->numero_compte, // <--- LE NUMÉRO DE COMPTE EST AJOUTÉ ICI
                    'tiers' => $m->tiers_nom,
                    'libelle' => $m->libelle_mouvement,
                    'entree' => $m->montant_debit > 0 ? $m->montant_debit : 0,
                    'sortie' => $m->montant_credit > 0 ? $m->montant_credit : 0,
                ])
            ];
        });

        return response()->json([
            'statut' => 'success',
            'solde_ouverture' => $donnees['solde_ouverture'],
            'groupes' => $groupes->values(),
            'total_general_debit' => $donnees['total_debit'],
            'total_general_credit' => $donnees['total_credit'],
            'solde_cloture' => $donnees['solde_cloture']
        ]);

    } catch (Exception $e) {
        return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 400);
    }
}
    /**
     * Exporte le journal de caisse en PDF avec regroupement par type
     */

    // ... haut du fichier identique

public function exportPdf(Request $request)
{
    $request->validate([
        'caisse_id'   => 'required|exists:caisses,id',
        'code_agence' => 'required|string',
        'date_debut'  => 'required|date',
        'date_fin'    => 'required|date|after_or_equal:date_debut',
    ]);

    try {
        $donnees = $this->caisseService->obtenirJournalCaisseComplet($request->all());
        $caisse = DB::table('caisses')->where('id', $request->caisse_id)->first();

        $groupesMouvements = collect($donnees['journal_groupe'])->map(function ($mouvements, $typeFlux) {
            return $mouvements->map(function ($mvt) use ($typeFlux) {
                
                $valeur = ($mvt->montant_debit > 0) ? $mvt->montant_debit : $mvt->montant_credit;

                return (object)[
                    'date'          => $mvt->date_mouvement,
                    'numero_compte' => $mvt->numero_compte,
                    'reference'     => $mvt->reference_operation,
                    'tiers'         => $mvt->tiers_final, // Utilise la logique Tiers > Physique > Morale du service
                    'libelle'       => $mvt->libelle_mouvement, // <--- L'OUBLI ÉTAIT ICI
                    'entree'        => ($typeFlux === 'VERSEMENTS' || $typeFlux === 'VERSEMENT') ? $valeur : 0,
                    'sortie'        => ($typeFlux === 'RETRAITS' || $typeFlux === 'RETRAIT') ? $valeur : 0,
                ];
            });
        });

        $pdf = Pdf::loadView('pdf.journal_caisse', [
            'ouverture'          => $donnees['solde_ouverture'],
            'groupes_mouvements' => $groupesMouvements,
            'total_debit'        => $donnees['total_debit'],
            'total_credit'       => $donnees['total_credit'],
            'solde_cloture'      => $donnees['solde_cloture'],
            'filtres'            => $request->all(),
            'code_caisse'        => $caisse->code_caisse ?? 'N/A'
        ])->setPaper('a4', 'landscape');

        return $pdf->download("journal_caisse_{$caisse->code_caisse}.pdf");

    } catch (Exception $e) {
        \Log::error("Erreur PDF: " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
}