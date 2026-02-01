<?php

namespace App\Services;

use App\Models\CreditApplication;

class CreditCalculationService
{
    /**
     * Frais d'étude (3%)
     */
    public function fraisEtude(float $montant): float
    {
        return round($montant * 0.03, 2);
    }

    /**
     * Frais de mise en place (0.5%)
     */
    public function fraisMiseEnPlace(float $montant): float
    {
        return round($montant * 0.005, 2);
    }

    /**
     * Intérêts composés annuels (3%)
     */
    public function interetsComposes(float $montant, int $dureeMois): float
    {
        $annees = $dureeMois / 12;
        $taux = 0.03;

        return round(($montant * pow(1 + $taux, $annees)) - $montant, 2);
    }

    /**
     * Échéancier simplifié
     */
    public function resumeFinancier(CreditApplication $credit): array
    {
        return [
            'montant' => $credit->montant,
            'frais_etude' => $this->fraisEtude($credit->montant),
            'frais_mise_en_place' => $this->fraisMiseEnPlace($credit->montant),
            'interets' => $this->interetsComposes($credit->montant, $credit->duree),
            'total_a_rembourser' =>
                $credit->montant +
                $this->interetsComposes($credit->montant, $credit->duree),
        ];
    }
}
