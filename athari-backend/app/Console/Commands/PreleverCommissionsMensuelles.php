<?php

namespace App\Console\Commands;

use App\Services\Compte\CommissionService;
use Illuminate\Console\Command;

class PreleverCommissionsMensuelles extends Command
{
    protected $signature = 'accounts:prelever-commissions {--mois= : Mois de prélèvement} {--annee= : Année de prélèvement}';
    protected $description = 'Prélève les commissions mensuelles sur tous les comptes éligibles';

    public function handle(CommissionService $commissionService): int
    {
        $mois = $this->option('mois') ?? now()->month;
        $annee = $this->option('annee') ?? now()->year;

        $this->info("Prélèvement des commissions pour {$mois}/{$annee}...");

        $results = $commissionService->preleverCommissionsMensuelles($mois, $annee);

        $this->info("Terminé: {$results->count()} commissions prélevées.");

        return Command::SUCCESS;
    }
}