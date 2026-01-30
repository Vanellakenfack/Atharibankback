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
   public function exportPdf(Request $request)
{
    $request->validate([
        'caisse_id'   => 'required|exists:caisses,id',
        'code_agence' => 'required|string',
        'date_debut'  => 'required|date',
        'date_fin'    => 'required|date|after_or_equal:date_debut',
    ]);

    try {
        // 1. On récupère les données déjà groupées du service
        $donnees = $this->caisseService->obtenirJournalCaisseComplet($request->all());
        $caisse = DB::table('caisses')->where('id', $request->caisse_id)->first();

        /**
         * 2. On adapte la structure 'journal_groupe' pour qu'elle corresponde 
         * aux noms de variables attendus par votre vue Blade (date, entree, sortie)
         */
  $groupesMouvements = collect($donnees['journal_groupe'])->map(function ($mouvements, $typeFlux) {
    return $mouvements->map(function ($mvt) use ($typeFlux) {
        // On détermine le montant à utiliser (on prend le débit ou le crédit selon ce qui est rempli)
        $valeur = ($mvt->montant_debit > 0) ? $mvt->montant_debit : $mvt->montant_credit;

        return (object)[
            'date'      => $mvt->date_mouvement,
            'reference' => $mvt->reference_operation,
            'tiers'     => $mvt->tiers_nom,
            'libelle'   => $mvt->libelle_mouvement,
            
            // Si le groupe est VERSEMENTS, on met le montant en entrée, sinon 0
            'entree'    => ($typeFlux === 'VERSEMENTS') ? $valeur : 0,
            
            // Si le groupe est RETRAITS, on met le montant en sortie, sinon 0
            'sortie'    => ($typeFlux === 'RETRAITS') ? $valeur : 0,
        ];
    });
});

        // 3. Préparation du PDF avec les clés attendues par votre Blade
        
        $pdf = Pdf::loadView('pdf.journal_caisse', [
            'ouverture'          => $donnees['solde_ouverture'], // Pour {{ $ouverture }} (compatibilité)
            'journal_groupe'     => $groupesMouvements,          // Pour la boucle principale
            'groupes_mouvements' => $groupesMouvements,          // Pour la boucle dans votre Blade
            'total_debit'        => $donnees['total_debit'],
            'total_credit'       => $donnees['total_credit'],
            'solde_cloture'      => $donnees['solde_cloture'],
            'cloture'            => $donnees['solde_cloture'],
            'filtres'            => $request->all(),
            'code_caisse'        => $caisse->code_caisse ?? 'N/A'
        ])->setPaper('a4', 'landscape');

        return $pdf->download("journal_caisse_{$caisse->code_caisse}.pdf");

    } catch (Exception $e) {
        // En cas d'erreur, il est préférable de logguer pour le debug
        \Log::error("Erreur PDF: " . $e->getMessage());
        return response()->json(['error' => "Erreur lors de la génération : " . $e->getMessage()], 400);
    }
}
}