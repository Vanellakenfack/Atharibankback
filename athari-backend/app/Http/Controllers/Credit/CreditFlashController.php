<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Credit\CreditType;
use App\Models\Credit\CreditApplication;
use App\Models\compte\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreditFlashController extends Controller
{
    /**
     * Soumettre une demande de crédit flash
     */
    public function store(Request $request): JsonResponse
    {
        $numeroDemande = 'FLASH-' . time();

        $request->validate([
            'client_id' => 'required|integer',
            'credit_type_id' => 'required|integer',
            'montant' => 'required|numeric',
            'duree' => 'required|integer',
            'source_revenus' => 'required|string',
            'revenus_mensuels' => 'required|numeric',
        ]);

        $creditType = CreditType::where('category', 'credit_flash')
            ->findOrFail($request->credit_type_id);

        $compte = Compte::where('client_id', $request->client_id)->first();

        if (!$compte) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun compte trouvé pour ce client'
            ], 404);
        }

        $compteId = $compte->id;

        // 📌 Récupération de la grille de tarification
        $details = json_decode($creditType->details_supplementaires, true);
        $grille = $details['grille_tarification'] ?? [];

        $palier = null;
        foreach ($grille as $p) {
            if ($request->montant >= $p['min'] && $request->montant <= $p['max']) {
                $palier = $p;
                break;
            }
        }

        if (!$palier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun palier correspondant au montant fourni'
            ], 400);
        }

        // 📌 Calculs
        $montant = floatval($request->montant);
        $fraisEtude = is_numeric($palier['frais_etude']) ? floatval($palier['frais_etude']) : 0;

        // Calcul des intérêts selon la grille (premier jour + journalier * (duree - 1))
        $interetTotal = floatval($palier['premier_jour']) + (floatval($palier['journalier']) * ($request->duree - 1));
        $penaliteParJour = floatval($palier['penalite_jour']);

        // Total à rembourser = montant + frais d'étude + intérêts
        $totalARembourser = $montant + $fraisEtude + $interetTotal;

        // Débogage
        Log::info('Calculs crédit flash détaillés:', [
            'montant' => $montant,
            'frais_etude' => $fraisEtude,
            'interet_total' => $interetTotal,
            'penalite_par_jour' => $penaliteParJour,
            'total_a_rembourser' => $totalARembourser,
            'source_revenus' => $request->source_revenus,
            'revenus_mensuels' => $request->revenus_mensuels,
        ]);

        // Création de la demande de crédit
        $application = CreditApplication::create([
            'numero_demande' => $numeroDemande,
            'compte_id' => $compteId,
            'credit_type_id' => $creditType->id,
            'montant' => $montant,
            'duree' => intval($request->duree),
            'taux_interet' => $creditType->taux_interet,
            'interet_total' => $interetTotal,
            'frais_etude' => $fraisEtude,
            'montant_total' => $totalARembourser,
            'penalite_par_jour' => $penaliteParJour,
            'source_revenus' => $request->source_revenus,
            'revenus_mensuels' => $request->revenus_mensuels,
            'statut' => 'SOUMIS',
            'date_demande' => Carbon::now(),
        ]);

        // Formatage de la réponse détaillée
        $responseData = [
            'id' => $application->id,
            'numero_demande' => $application->numero_demande,
            'compte_id' => $application->compte_id,
            'credit_type_id' => $application->credit_type_id,
            'montant_emprunte' => number_format($application->montant, 2, '.', ' ') . ' FCFA',
            'duree_jours' => $application->duree,
            'taux_interet' => $application->taux_interet . '%',

            // Détails des frais et intérêts
            'details_calcul' => [
                'frais_etude' => number_format($application->frais_etude, 2, '.', ' ') . ' FCFA',
                'interet_total' => number_format($application->interet_total, 2, '.', ' ') . ' FCFA',
                'penalite_par_jour' => number_format($application->penalite_par_jour, 2, '.', ' ') . ' FCFA/jour',
            ],

            // Informations revenus
            'informations_revenus' => [
                'source_revenus' => $application->source_revenus,
                'revenus_mensuels' => number_format($application->revenus_mensuels, 2, '.', ' ') . ' FCFA',
            ],

            // Totaux
            'total_a_rembourser' => number_format($application->montant_total, 2, '.', ' ') . ' FCFA',
            'composition_total' => [
                'montant_principal' => number_format($montant, 2, '.', ' ') . ' FCFA',
                'frais_etude' => number_format($fraisEtude, 2, '.', ' ') . ' FCFA',
                'interets' => number_format($interetTotal, 2, '.', ' ') . ' FCFA',
                'total' => number_format($totalARembourser, 2, '.', ' ') . ' FCFA',
            ],

            // Statut et dates
            'statut' => $application->statut,
            'date_demande' => $application->date_demande,
            'created_at' => $application->created_at,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Demande de crédit flash soumise avec succès',
            'data' => $responseData
        ]);
    }
}
