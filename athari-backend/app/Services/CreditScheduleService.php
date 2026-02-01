<?php

namespace App\Services;

use App\Models\CreditApplication;
use App\Services\CreditCalculationService;

class CreditScheduleService
{
    protected CreditCalculationService $calculator;

    public function __construct(CreditCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Générer l'échéancier mensuel
     */
    public function generate(CreditApplication $credit): array
    {
        $montant = $credit->montant;
        $duree = $credit->duree; // en mois
        $interets = $this->calculator->interetsComposes($montant, $duree);
        $total = $montant + $interets;
        $mensualite = round($total / $duree, 2);

        $schedule = [];
        $date = now();

        for ($i = 1; $i <= $duree; $i++) {
            $schedule[] = [
                'mois' => $i,
                'date' => $date->copy()->addMonths($i)->format('Y-m-d'),
                'montant' => $mensualite,
                'principal' => round($montant / $duree, 2),
                'interet' => round($interets / $duree, 2),
                'penalite' => 0, // sera calculé si retard
                'statut' => 'EN_COURS'
            ];
        }

        return $schedule;
    }

    /**
     * Ajouter pénalité en cas de retard
     */
    public function applyPenalty(array &$schedule, int $moisRetard, float $penaliteTaux)
    {
        if (isset($schedule[$moisRetard - 1])) {
            $schedule[$moisRetard - 1]['penalite'] =
                round($schedule[$moisRetard - 1]['montant'] * $penaliteTaux / 100, 2);
            $schedule[$moisRetard - 1]['statut'] = 'EN_RETARD';
        }
    }
}
