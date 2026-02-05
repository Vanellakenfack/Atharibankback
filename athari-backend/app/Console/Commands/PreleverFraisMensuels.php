<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compte\Compte;
use App\Models\compte\FraisEnAttente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreleverFraisMensuels extends Command
{
    /**
     * Le nom et la signature de la commande.
     */
    protected $signature = 'banque:prelever-frais';

    /**
     * La description de la commande.
     */
    protected $description = 'Prélève les commissions mensuelles ou les place en attente si solde insuffisant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début du prélèvement des frais mensuels...');

        // 1. Récupérer les comptes actifs qui ont des commissions mensuelles activées
        $comptes = Compte::where('statut', 'actif')
            ->whereHas('typeCompte', function($query) {
                $query->where('commission_mensuelle_actif', true);
            })->get();

        foreach ($comptes as $compte) {
            // Éviter les doubles prélèvements si la commande est lancée deux fois par erreur
            if ($compte->date_dernier_prelevement_frais && 
                $compte->date_dernier_prelevement_frais->isCurrentMonth()) {
                continue;
            }

            $montantAFrapper = $compte->typeCompte->commission_mensuel;

            DB::transaction(function () use ($compte, $montantAFrapper) {
                if ($compte->solde >= $montantAFrapper) {
                    // CAS 1 : Solde suffisant -> On prélève directement
                    $compte->decrement('solde', $montantAFrapper);
                    $compte->update([
                        'date_dernier_prelevement_frais' => now()
                    ]);

                    // Ici, vous devriez enregistrer une transaction comptable (Table transactions)
                    $this->info("Prélèvement réussi pour le compte: {$compte->numero_compte}");
                } else {
                    // CAS 2 : Solde insuffisant -> On enregistre dans les frais en attente
                    FraisEnAttente::create([
                        'compte_id' => $compte->id,
                        'montant'   => $montantAFrapper,
                        'mois'      => now()->month,
                        'annee'     => now()->year,
                        'statut'    => 'en_attente'
                    ]);

                    // On met à jour la date pour ne pas retenter demain
                    $compte->update([
                        'date_dernier_prelevement_frais' => now()
                    ]);

                    $this->warn("Solde insuffisant pour {$compte->numero_compte}. Mis en attente.");
                }
            });
        }

        $this->info('Opération terminée.');
    }
}