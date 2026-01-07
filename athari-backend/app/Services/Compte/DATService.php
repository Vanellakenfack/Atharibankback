<?php

namespace App\Services\Compte;

use App\Models\Compte\ContratDat;
use App\Models\Compte\DatType;
use App\Models\Compte\MouvementComptable;
use App\Models\Compte\Compte;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class DATService
{
    /**
     * ÉTAPE 1 : CRÉATION EN ATTENTE (Saisie)
     * Remplace l'activation immédiate pour permettre la validation par le CA/DG.
     */
    public function creerContratEnAttente(array $data)
    {
        return DB::transaction(function () use ($data) {
            $type = DatType::findOrFail($data['dat_type_id']);

            return ContratDat::create([
                'numero_ordre'             => $this->genererNumeroOrdre(),
                'statut'                   => 'EN_ATTENTE', // Statut pour le workflow de validation
                'dat_type_id'              => $data['dat_type_id'],
                'account_id'               => $data['account_id'],
                'client_source_account_id' => $data['client_source_account_id'],
                'destination_interet_id'   => $data['destination_interet_id'] ?? $data['client_source_account_id'],
                'destination_capital_id'   => $data['destination_capital_id'] ?? $data['client_source_account_id'],
                'montant_initial'          => $data['montant'],
                'montant_actuel'           => $data['montant'],
                'taux_interet_annuel'      => $type->taux_interet,
                'taux_penalite_anticipe'   => $type->taux_penalite ?? 0,
                'duree_mois'               => $type->duree_mois,
                'periodicite'              => $data['periodicite'] ?? 'E',
                'mode_versement'           => $data['mode_versement'] ?? 'CAPITALISATION',
                'is_blocked'               => true,
                'nb_tranches_actuel'       => 1,
                'is_precompte'             => $data['is_precompte'] ?? false,
                'is_jours_reels'           => $data['is_jours_reels'] ?? true,
                'date_valeur'     => $data['date_valeur'], // <--- AJOUTER CETTE LIGNE

                'date_execution'       => $data['date_execution'], // <--- AJOUTER CECI
                'date_maturite'    => $data['date_maturite'],  //
            ]);
        });
    }

    /**
     * ÉTAPE 2 : VALIDATION ET ACTIVATION COMPTABLE
     * Appelée par le contrôleur (méthode valider) après approbation du CA/DG/CC.
     */
    public function validerEtActiver(ContratDat $contrat)
    {
        return DB::transaction(function () use ($contrat) {
            $compteSource = Compte::findOrFail($contrat->client_source_account_id);
            $compteDatInterne = Compte::findOrFail($contrat->account_id);

            if ($compteSource->solde < $contrat->montant_initial) {
                throw new Exception("Solde insuffisant sur le compte client pour activer ce DAT.");
            }

            // 1. Mouvements Comptables
            $compteSource->decrement('solde', $contrat->montant_initial);
            $compteDatInterne->increment('solde', $contrat->montant_initial);

            $this->enregistrerMouvement($compteSource->id, $contrat->montant_initial, 'DEBIT', "Activation DAT {$contrat->numero_ordre}");
            $this->enregistrerMouvement($compteDatInterne->id, $contrat->montant_initial, 'CREDIT', "Dépôt initial DAT {$contrat->numero_ordre}");

            // 2. Mise à jour des dates et statut
            $contrat->update([
                'statut'         => 'ACTIF',
                'date_execution' => now(),
                'date_valeur'    => now(),
                'date_maturite'  => now()->addMonths($contrat->duree_mois),
            ]);

            return $contrat;
        });
    }

    /**
     * INITIALISATION ET ACTIVATION (Ancienne méthode conservée pour compatibilité)
     */
    public function initialiserEtActiver(array $data)
    {
        $contrat = $this->creerContratEnAttente($data);
        return $this->validerEtActiver($contrat);
    }

    /**
     * AJOUTER UN VERSEMENT (FONCTION DÉPOSER)
     */
    public function ajouterVersement(ContratDat $contrat, $montant)
    {
        return DB::transaction(function () use ($contrat, $montant) {
            $compteSource = Compte::findOrFail($contrat->client_source_account_id);
            $compteDatInterne = Compte::findOrFail($contrat->account_id);

            if ($compteSource->solde < $montant) {
                throw new Exception("Solde insuffisant pour ce versement supplémentaire.");
            }

            $compteSource->decrement('solde', $montant);
            $compteDatInterne->increment('solde', $montant);

            $this->enregistrerMouvement($compteSource->id, $montant, 'DEBIT', "Versement sup. DAT {$contrat->numero_ordre}");
            $this->enregistrerMouvement($compteDatInterne->id, $montant, 'CREDIT', "Tranche DAT {$contrat->numero_ordre}");

            $contrat->increment('montant_actuel', $montant);
            $contrat->increment('nb_tranches_actuel');

            return $contrat;
        });
    }

    /**
     * CALCUL DE SORTIE
     */
    public function calculerDetailsSortie(ContratDat $contrat)
    {
        $maintenant = now();
        $dateMaturite = Carbon::parse($contrat->date_maturite);
        $estAnticipe = $maintenant->lt($dateMaturite);
        
        $joursPasses = Carbon::parse($contrat->date_valeur)->diffInDays($maintenant);
        $interetsCourus = ($contrat->montant_actuel * ($contrat->taux_interet_annuel / 100) * $joursPasses) / 365;

        $penalite = 0;
        if ($estAnticipe) {
            $penalite = $contrat->montant_actuel * ($contrat->taux_penalite_anticipe / 100);
        }

        return [
            'capital_actuel'  => (float)$contrat->montant_actuel,
            'interets_courus' => round($interetsCourus, 0),
            'penalite'        => round($penalite, 0),
            'net_a_payer'     => round(($contrat->montant_actuel + $interetsCourus) - $penalite, 0),
            'est_anticipe'    => $estAnticipe
        ];
    }

    /**
     * CLÔTURE DÉFINITIVE
     */
    public function cloturerContrat(ContratDat $contrat)
    {
        return DB::transaction(function () use ($contrat) {
            $details = $this->calculerDetailsSortie($contrat);
            
            $compteDatInterne = Compte::findOrFail($contrat->account_id);
            $compteDestCap = Compte::findOrFail($contrat->destination_capital_id);
            $compteDestInt = Compte::findOrFail($contrat->destination_interet_id);

            $compteDatInterne->decrement('solde', $contrat->montant_actuel);
            
            $montantCapitalNet = $details['capital_actuel'] - $details['penalite'];
            $compteDestCap->increment('solde', $montantCapitalNet);
            
            if ($details['interets_courus'] > 0) {
                $compteDestInt->increment('solde', $details['interets_courus']);
            }

            $this->enregistrerMouvement($compteDatInterne->id, $contrat->montant_actuel, 'DEBIT', "Fermeture DAT {$contrat->numero_ordre}");
            $this->enregistrerMouvement($compteDestCap->id, $montantCapitalNet, 'CREDIT', "Retour Capital DAT");

            $contrat->update([
                'statut' => $details['est_anticipe'] ? 'ANTICIPE' : 'CLOTURE',
                'is_blocked' => false,
                'date_cloture_reelle' => now()
            ]);

            return $details;
        });
    }

   private function enregistrerMouvement($accountId, $montant, $sens, $libelle)
{
    // Important : On s'assure que $accountId n'est pas nul
    if (!$accountId) {
        throw new \Exception("ID de compte manquant");
    }

    return MouvementComptable::create([
        'compte_id'         => $accountId,        // Vérifiez que c'est bien l'ID (ex: 4)
        'date_mouvement'    => now(),
        'libelle_mouvement' => $libelle,
        'montant_debit'     => ($sens === 'DEBIT') ? $montant : 0,
        'montant_credit'    => ($sens === 'CREDIT') ? $montant : 0,
        'compte_debit_id'   => 500,                // Doit exister dans plan_comptable
        'compte_credit_id'  => 500,               // Doit exister dans plan_comptable
        'journal'           => 'BANQUE',
        'statut'            => 'COMPTABILISE',
    ]);
}

    private function genererNumeroOrdre()
    {
        $count = ContratDat::whereYear('created_at', date('Y'))->count() + 1;
        return "DAT-" . date('Y') . "-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}