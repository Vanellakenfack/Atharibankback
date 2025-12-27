<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\compte\Compte;
use App\Models\compte\frais\ParametrageFrais;
use App\Models\compte\frais\FraisApplique;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalculerInterets extends Command
{
    protected $signature = 'frais:interets';
    protected $description = 'Calculer et appliquer les intérêts créditeurs';

    public function handle()
    {
        $this->info('Calcul des intérêts créditeurs...');
        
        // Comptes DAT
        $comptesDAT = Compte::whereHas('typeCompte', function ($query) {
            $query->where('code', 'DAT');
        })->actif()->get();

        foreach ($comptesDAT as $compte) {
            $this->calculerInteretsDAT($compte);
        }

        // Comptes bloqués
        $comptesBloques = Compte::whereNotNull('duree_blocage_mois')
            ->actif()
            ->get();

        foreach ($comptesBloques as $compte) {
            $this->calculerInteretsBloques($compte);
        }

        $this->info('Calcul des intérêts terminé.');
        
        return Command::SUCCESS;
    }

    private function calculerInteretsDAT(Compte $compte)
    {
        $fraisInteret = ParametrageFrais::where('type_frais', 'INTERET')
            ->where('type_compte_id', $compte->type_compte_id)
            ->actif()
            ->first();

        if (!$fraisInteret || $fraisInteret->taux_pourcentage === null) {
            return;
        }

        $montantInteret = $compte->solde * ($fraisInteret->taux_pourcentage / 100) / 12;

        if ($montantInteret > 0) {
            FraisApplique::create([
                'compte_id' => $compte->id,
                'parametrage_frais_id' => $fraisInteret->id,
                'date_application' => now()->endOfMonth(),
                'montant_calcule' => $montantInteret,
                'base_calcul_valeur' => $compte->solde,
                'methode_calcul' => 'POURCENTAGE_SOLDE',
                'statut' => 'PRELEVE',
                'compte_produit_id' => $fraisInteret->compte_produit_id,
                'compte_client_id' => $compte->plan_comptable_id,
                'reference_comptable' => 'INT-' . now()->format('Ym') . '-' . $compte->id,
            ]);

            // Ajouter les intérêts au solde
            $compte->solde += $montantInteret;
            $compte->save();
        }
    }

    private function calculerInteretsBloques(Compte $compte)
    {
        // Calculer les intérêts journaliers
        $fraisInteret = ParametrageFrais::where('type_frais', 'INTERET')
            ->where('type_compte_id', $compte->type_compte_id)
            ->actif()
            ->first();

        if (!$fraisInteret || $fraisInteret->taux_pourcentage === null) {
            return;
        }

        // Intérêt journalier
        $tauxJournalier = $fraisInteret->taux_pourcentage / 365;
        $montantInteret = $compte->solde * ($tauxJournalier / 100);

        // Ajouter aux intérêts accumulés (à capitaliser à la fin de la période)
        // Ici on pourrait stocker dans un champ séparé
        $compte->solde += $montantInteret;
        $compte->save();
    }
}