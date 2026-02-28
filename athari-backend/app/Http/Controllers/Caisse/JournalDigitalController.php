<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CaisseTransactionDigitale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Carbon\Carbon;

class JournalDigitalController extends Controller
{
    /**
     * Récupère le journal digital (utilisé par Route::get('/journal-digital', ...)->name('journal.digital'))
     */
    public function index(Request $request)
    {
        try {
            // Validation des dates
            $request->validate([
                'date_debut' => 'required|date',
                'date_fin'   => 'required|date|after_or_equal:date_debut',
                'agence_id'  => 'nullable',
                'operateur'  => 'nullable|string',
            ]);

            $debut = Carbon::parse($request->date_debut)->startOfDay();
            $fin = Carbon::parse($request->date_fin)->endOfDay();

            $query = CaisseTransactionDigitale::with(['compteBancaire.client', 'agence', 'caissier'])
                ->whereBetween('date_operation', [$debut, $fin]);

            if ($request->filled('agence_id') && $request->agence_id !== 'all') {
                $query->where('agence_id', $request->agence_id);
            }

            if ($request->filled('operateur') && $request->operateur !== 'all') {
                $query->where('operateur', $request->operateur);
            }

            $transactions = $query->orderBy('date_operation', 'desc')->get();

            // Structuration pour le frontend React
            $groupes = $transactions->groupBy('type_flux')->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'total_montant' => $items->sum('montant_brut'),
                    'total_commissions' => $items->sum('commissions'),
                    'operations' => $items->map(fn($t) => [
                        'id' => $t->id,
                        'date' => $t->date_operation,
                        'ref' => $t->reference_unique,
                        'operateur' => $t->operateur,
                        'compte' => $t->compteBancaire->numero_compte ?? 'PASSAGE',
                        'tiers' => $t->compteBancaire->client->nom_complet ?? 'Client Passage',
                        'telephone' => $t->telephone_client,
                        'ref_sim' => $t->reference_operateur,
                        'entree' => $t->type_flux === 'VERSEMENT' ? $t->montant_brut : 0,
                        'sortie' => $t->type_flux === 'RETRAIT' ? $t->montant_brut : 0,
                        'commission' => $t->commissions,
                    ])
                ];
            });

            return response()->json([
                'statut' => 'success',
                'groupes' => $groupes->values(),
                'total_entrees' => $transactions->where('type_flux', 'VERSEMENT')->sum('montant_brut'),
                'total_sorties' => $transactions->where('type_flux', 'RETRAIT')->sum('montant_brut'),
                'total_commissions' => $transactions->sum('commissions'),
            ]);

        } catch (Exception $e) {
            return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Exporte le journal en PDF (utilisé par Route::get('/journal-digital/export-pdf', ...))
     */
    public function genererPdf(Request $request)
    {
        try {
            // Récupération des paramètres
            $date_debut = $request->date_debut ?? now()->startOfMonth()->format('Y-m-d');
            $date_fin = $request->date_fin ?? now()->format('Y-m-d');

            $debut = Carbon::parse($date_debut)->startOfDay();
            $fin = Carbon::parse($date_fin)->endOfDay();

            $query = CaisseTransactionDigitale::with(['compteBancaire.client', 'agence', 'caissier'])
                ->whereBetween('date_operation', [$debut, $fin]);

            if ($request->filled('agence_id') && $request->agence_id !== 'all') {
                $query->where('agence_id', $request->agence_id);
            }

            if ($request->filled('operateur') && $request->operateur !== 'all') {
                $query->where('operateur', $request->operateur);
            }

            $transactions = $query->orderBy('date_operation', 'asc')->get();
            
            // Préparation des données pour la vue Blade
            $data = [
                'groupes'           => $transactions->groupBy('type_flux'),
                'date_debut'        => $debut->format('d/m/Y'),
                'date_fin'          => $fin->format('d/m/Y'),
                'total_entrees'     => $transactions->where('type_flux', 'VERSEMENT')->sum('montant_brut'),
                'total_sorties'     => $transactions->where('type_flux', 'RETRAIT')->sum('montant_brut'),
                'total_commissions' => $transactions->sum('commissions'),
                'operateur'         => $request->operateur ?? 'Tous',
                'filtres'           => [
                    'agence' => $transactions->first()?->agence?->nom_agence ?? 'Toutes les agences'
                ],
                'date_gen'          => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('pdf.journal_digital', $data)
                      ->setPaper('a4', 'landscape');

            return $pdf->download("journal_digital_" . date('Ymd_His') . ".pdf");

        } catch (Exception $e) {
            \Log::error("Erreur PDF Digital: " . $e->getMessage());
            return response()->json(['error' => "Erreur lors de la génération du PDF"], 400);
        }
    }
}