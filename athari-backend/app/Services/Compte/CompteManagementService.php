<?php

namespace App\Services\Compte;

use App\Models\compte\FraisEnAttente;
use App\Models\Compte\Compte;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CompteManagementService
{
    /**
     * Vérifie si le retrait est autorisé selon le type de compte et l'échéance
     */
    public function verifierEligibiliteRetrait(Compte $compte)
    {
        $compte->loadMissing('typeCompte');

        // 1. Si le compte est bloqué administrativement
        if ($compte->statut === 'bloqué') {
            throw new Exception("Opération impossible : Le compte est bloqué.");
        }

        // 2. Logique pour les comptes avec durée (a_vue = false)
        if (!$compte->typeCompte->a_vue) {
            
            $aujourdhui = Carbon::now();
            $dateEcheance = $compte->date_echeance ? Carbon::parse($compte->date_echeance) : null;

            // RÈGLE : Si le compte n'a pas atteint sa date d'échéance
            if ($dateEcheance && $aujourdhui->lt($dateEcheance)) {
                
                // Vérifier si le retrait anticipé est autorisé dans la config du type de compte
                if (!$compte->typeCompte->retrait_anticipe_autorise) {
                    throw new Exception(
                        "Retrait refusé : La période de blocage n'est pas arrivée à échéance (Prévue le : " . 
                        $dateEcheance->format('d/m/Y') . ")."
                    );
                }
                
                // Note : Si autorisé mais anticipé, vous pourriez ici calculer des pénalités
            }
        }
    }

    /**
     * Ré-applique le blocage après l'opération si nécessaire
     */
    public function gererStatutApresRetrait(Compte $compte)
    {
        // Si c'est un compte bloqué par nature, on le remet en statut bloqué
        if (!$compte->typeCompte->a_vue) {
            $compte->update([
                'statut' => 'bloqué',
                'date_blocage' => now(),
                'motif_blocage' => 'Reblocage automatique après retrait.'
            ]);
        }
    }

    public function gererCycleDeVieApresRetrait(Compte $compte) {
        if (!$compte->typeCompte->a_vue) {
            $compte->update([
                'statut' => 'actif', 
                'date_echeance' => now()->addMonths($compte->duree_blocage_mois)
            ]);
        }
    }

    /** Récupère les dettes dès qu'un versement est fait */
    public function apurerDettes(Compte $compte) {
        $dettes = FraisEnAttente::where('compte_id', $compte->id)
            ->where('statut', 'en_attente')->get();

        foreach ($dettes as $dette) {
            if ($compte->solde >= $dette->montant) {
                DB::transaction(function () use ($compte, $dette) {
                    $compte->decrement('solde', $dette->montant);
                    $dette->update(['statut' => 'recupere']);
                    // Ici, ajoutez l'écriture comptable vers le chapitre commission
                });
            }
        }
    }
}