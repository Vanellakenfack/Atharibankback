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
     * INITIALISATION ET ACTIVATION DU CONTRAT
     */
    public function initialiserEtActiver($accountId, $datTypeId, $montant, $modeVersement = 'CAPITALISATION')
    {
        return DB::transaction(function () use ($accountId, $datTypeId, $montant, $modeVersement) {
            $type = DatType::findOrFail($datTypeId);
            $compte = Compte::findOrFail($accountId);

            if ($compte->solde < $montant) {
                throw new Exception("Solde insuffisant pour ouvrir ce DAT.");
            }

            $contrat = ContratDat::create([
                'account_id'              => $accountId,
                'dat_type_id'             => $datTypeId,
                'taux_interet_annuel'     => $type->taux_interet,
                'taux_penalite_anticipe'  => $type->taux_penalite ?? 0.10,
                'duree_mois'              => $type->duree_mois,
                'capital_initial'         => $montant,
                'montant_actuel'          => $montant,
                'mode_versement'          => $modeVersement,
                'is_blocked'              => true,
                'date_ouverture'          => now(),
                'date_scellage'           => now(),
                'date_maturite_prevue'    => now()->addMonths($type->duree_mois),
                // Utilisation des IDs vers la table plan_comptable
                'plan_comptable_interet_id'  => $type->plan_comptable_interet_id,
                'plan_comptable_penalite_id' => $type->plan_comptable_penalite_id,
            ]);

            $compte->decrement('solde', $montant);

            $this->enregistrerMouvement($accountId, $montant, 'DEBIT', "Souscription DAT #{$contrat->id}");

            return $contrat;
        });
    }

    /**
     * CALCULS DE SORTIE
     */
    public function calculerDetailsSortie(ContratDat $contrat)
    {
        // Charger les libellés du plan comptable pour les mouvements
        $contrat->load(['compteInteret', 'comptePenalite']);

        $baseCalcul = $contrat->montant_actuel;
        $maintenant = now();
        $dateOuverture = Carbon::parse($contrat->date_ouverture);
        
        $joursPasses = $dateOuverture->diffInDays($maintenant);
        $interetsGagnes = ($baseCalcul * $contrat->taux_interet_annuel * $joursPasses) / 360;

        $estAnticipe = $maintenant->lt(Carbon::parse($contrat->date_maturite_prevue));
        $montantPenalite = $estAnticipe ? ($contrat->capital_initial * $contrat->taux_penalite_anticipe) : 0;

        return [
            'capital_initial' => $contrat->capital_initial,
            'capital_actuel'  => $baseCalcul,
            'interets'        => round($interetsGagnes, 0),
            'penalite'        => round($montantPenalite, 0),
            'net_a_payer'     => round(($baseCalcul + $interetsGagnes) - $montantPenalite, 0),
            'est_anticipe'    => $estAnticipe,
            // Récupération des libellés depuis la relation plan_comptable
            'libelle_int'     => $contrat->compteInteret->libelle ?? "Intérêts DAT",
            'libelle_pen'     => $contrat->comptePenalite->libelle ?? "Pénalités DAT"
        ];
    }

    /**
     * CLÔTURE FINALE DU CONTRAT
     */
    public function cloturerContrat(ContratDat $contrat)
    {
        $details = $this->calculerDetailsSortie($contrat);

        return DB::transaction(function () use ($contrat, $details) {
            $contrat->compte->increment('solde', $details['net_a_payer']);

            $this->enregistrerMouvement($contrat->account_id, $details['capital_actuel'], 'CREDIT', "Remboursement Capital DAT #{$contrat->id}");
            
            if ($details['interets'] > 0) {
                $this->enregistrerMouvement($contrat->account_id, $details['interets'], 'CREDIT', $details['libelle_int']);
            }

            if ($details['penalite'] > 0) {
                $this->enregistrerMouvement($contrat->account_id, $details['penalite'], 'DEBIT', $details['libelle_pen']);
            }

            $contrat->update([
                'statut' => 'TERMINE',
                'is_blocked' => false,
                'date_cloture' => now()
            ]);

            return $details;
        });
    }

    /**
     * ENREGISTREMENT MOUVEMENT
     */
    private function enregistrerMouvement($accountId, $montant, $sens, $libelle)
    {
        return MouvementComptable::create([
            'account_id' => $accountId,
            'montant' => $montant,
            'sens' => $sens,
            'libelle' => $libelle,
            'date_operation' => now()
        ]);
    }

    /**
     * VERSEMENT COMPLÉMENTAIRE
     */
    public function ajouterVersement(ContratDat $contrat, $montant, $dureeMois)
    {
        return DB::transaction(function () use ($contrat, $montant, $dureeMois) {
            $compte = $contrat->compte;

            if ($contrat->statut !== 'ACTIF' && $contrat->statut !== 'EN_ATTENTE') {
                throw new Exception("État du contrat invalide.");
            }

            $contrat->increment('montant_actuel', $montant);
            $contrat->increment('nb_tranches_actuel');
            
            if ($contrat->nb_tranches_actuel == 1) {
                $contrat->date_scellage = now();
                $contrat->date_maturite_prevue = now()->addMonths($dureeMois);
                $contrat->statut = 'ACTIF';
            }

            $contrat->save();
            $compte->decrement('solde', $montant);

            $this->enregistrerMouvement($contrat->account_id, $montant, 'DEBIT', "Versement tranche DAT #{$contrat->id}");

            return $contrat;
        });
    }
}