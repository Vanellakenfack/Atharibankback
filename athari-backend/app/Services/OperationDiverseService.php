<?php

namespace App\Services;

use App\Models\OD\OperationDiverse;
use App\Models\OD\OdCollecte;
use App\Models\Compte\MouvementComptable;
use App\Models\chapitre\PlanComptable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OperationDiverseService
{
    /**
     * Traiter une collecte MATA BOOST
     */
    public function traiterCollecteMataBoost(array $data, User $saisisseur)
    {
        return DB::transaction(function () use ($data, $saisisseur) {
            // Créer l'OD
            $od = OperationDiverse::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'],
                'type_operation' => 'MATA_BOOST',
                'libelle' => 'Collecte MATA BOOST ' . ($data['type_mata'] === 'A_VUE' ? 'à vue' : 'bloqué'),
                'montant' => $data['montant'],
                'devise' => 'FCFA',
                'compte_collecteur_id' => $this->getCompteId('468'),
                'type_mata' => $data['type_mata'],
                'numero_bordereau' => $data['numero_bordereau'],
                'saisi_par' => $saisisseur->id,
            ]);

            // Créer la collecte
            OdCollecte::create([
                'operation_diverse_id' => $od->id,
                'gestionnaires_id' => $data['gestionnaires_id'],
                'type_collecte' => 'MATA_BOOST',
                'type_mata' => $data['type_mata'],
                'numero_bordereau' => $data['numero_bordereau'],
                'date_collecte' => $data['date_operation'],
            ]);

            return $od;
        });
    }

    /**
     * Traiter une collecte épargne journalière
     */
    public function traiterCollecteEpargneJournaliere(array $data, User $saisisseur)
    {
        return DB::transaction(function () use ($data, $saisisseur) {
            // Créer l'OD
            $od = OperationDiverse::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'],
                'type_operation' => 'EPARGNE_JOURNALIERE',
                'libelle' => 'Collecte épargne journalière',
                'montant' => $data['montant'],
                'devise' => 'FCFA',
                'compte_collecteur_id' => $this->getCompteId('468'),
                'numero_bordereau' => $data['numero_bordereau'],
                'saisi_par' => $saisisseur->id,
            ]);

            // Créer la collecte
            OdCollecte::create([
                'operation_diverse_id' => $od->id,
                'gestionnaires_id' => $data['gestionnaires_id'],
                'type_collecte' => 'EPARGNE_JOURNALIERE',
                'numero_bordereau' => $data['numero_bordereau'],
                'date_collecte' => $data['date_operation'],
            ]);

            return $od;
        });
    }

    /**
     * Traiter une charge
     */
    public function traiterCharge(array $data, $justificatifFile, User $saisisseur)
    {
        return DB::transaction(function () use ($data, $justificatifFile, $saisisseur) {
            // Créer l'OD
            $od = OperationDiverse::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'],
                'type_operation' => 'CHARGE',
                'libelle' => $data['libelle'],
                'montant' => $data['montant'],
                'devise' => 'FCFA',
                'compte_debit_id' => $data['compte_debit_id'],
                'compte_credit_id' => $this->getCompteId('47'), // Compte passage
                'justificatif_type' => $data['justificatif_type'],
                'justificatif_numero' => $data['justificatif_numero'],
                'justificatif_date' => $data['justificatif_date'],
                'saisi_par' => $saisisseur->id,
            ]);

            // Uploader le justificatif
            if ($justificatifFile) {
                $path = $justificatifFile->store(
                    "od/justificatifs/{$od->id}",
                    'public'
                );
                $od->update(['justificatif_path' => $path]);
            }

            return $od;
        });
    }

/**
 * Comptabiliser une OD
 */
public function comptabiliserOD(OperationDiverse $od, User $comptable)
{
    if ($od->est_comptabilise || $od->statut !== 'VALIDE') {
        throw new \Exception('OD non éligible pour la comptabilisation');
    }

    return DB::transaction(function () use ($od, $comptable) {
        // Décoder les JSON pour avoir les détails des comptes
        $comptesDebits = json_decode($od->comptes_debits_json, true) ?? [];
        $comptesCredits = json_decode($od->comptes_credits_json, true) ?? [];
        
        $mouvements = [];
        
        // CAS 1: Comptabilisation avec plusieurs comptes (débits et crédits)
        if (!empty($comptesDebits) || !empty($comptesCredits)) {
            
            // Créer les mouvements pour les débits
            foreach ($comptesDebits as $debit) {
                $mouvement = MouvementComptable::create([
                    'date_mouvement' => $od->date_operation,
                    'date_valeur' => $od->date_valeur ?? $od->date_operation,
                    'date_comptable' => $od->date_comptable ?? $od->date_operation,
                    'libelle_mouvement' => $debit['libelle'] ?? "OD {$od->numero_od}: {$od->libelle} (débit)",
                    'description' => $od->description,
                    'compte_debit_id' => $debit['compte_id'],  // ID du plan comptable
                    'compte_credit_id' => null,
                    'montant_debit' => $debit['montant'],
                    'montant_credit' => 0,
                    'journal' => $this->getJournalForType($od->type_operation),
                    'numero_piece' => $od->numero_piece,
                    'reference_operation' => $od->numero_od,
                    'statut' => 'COMPTABILISE',
                    'created_by' => $comptable->id,
                    'od_id' => $od->id,
                    'agence_id' => $od->agence_id,
                    'jours_comptable_id' => $od->jours_comptable_id,
                ]);
                $mouvements[] = $mouvement;
                
                \Log::info('Mouvement débit créé', [
                    'compte_debit_id' => $debit['compte_id'],
                    'montant' => $debit['montant'],
                    'mouvement_id' => $mouvement->id
                ]);
            }
            
            // Créer les mouvements pour les crédits
            foreach ($comptesCredits as $credit) {
                $mouvement = MouvementComptable::create([
                    'date_mouvement' => $od->date_operation,
                    'date_valeur' => $od->date_valeur ?? $od->date_operation,
                    'date_comptable' => $od->date_comptable ?? $od->date_operation,
                    'libelle_mouvement' => $credit['libelle'] ?? "OD {$od->numero_od}: {$od->libelle} (crédit)",
                    'description' => $od->description,
                    'compte_debit_id' => null,
                    'compte_credit_id' => $credit['compte_id'],  // ID du plan comptable
                    'montant_debit' => 0,
                    'montant_credit' => $credit['montant'],
                    'journal' => $this->getJournalForType($od->type_operation),
                    'numero_piece' => $od->numero_piece,
                    'reference_operation' => $od->numero_od,
                    'statut' => 'COMPTABILISE',
                    'created_by' => $comptable->id,
                    'od_id' => $od->id,
                    'agence_id' => $od->agence_id,
                    'jours_comptable_id' => $od->jours_comptable_id,
                ]);
                $mouvements[] = $mouvement;
                
                \Log::info('Mouvement crédit créé', [
                    'compte_credit_id' => $credit['compte_id'],
                    'montant' => $credit['montant'],
                    'mouvement_id' => $mouvement->id
                ]);
            }
        } 
        // CAS 2: Comptabilisation simple (un débit, un crédit)
        else {
            // Créer le mouvement comptable
            $mouvement = MouvementComptable::create([
                'date_mouvement' => $od->date_operation,
                'date_valeur' => $od->date_valeur ?? $od->date_operation,
                'date_comptable' => $od->date_comptable ?? $od->date_operation,
                'libelle_mouvement' => "OD {$od->numero_od}: {$od->libelle}",
                'description' => $od->description,
                'compte_debit_id' => $od->compte_debit_id,      // ID du plan comptable
                'compte_credit_id' => $od->compte_credit_id,    // ID du plan comptable
                'montant_debit' => $od->montant,
                'montant_credit' => $od->montant,
                'journal' => $this->getJournalForType($od->type_operation),
                'numero_piece' => $od->numero_piece,
                'reference_operation' => $od->numero_od,
                'statut' => 'COMPTABILISE',
                'created_by' => $comptable->id,
                'od_id' => $od->id,
                'agence_id' => $od->agence_id,
                'jours_comptable_id' => $od->jours_comptable_id,
            ]);
            
            $mouvements[] = $mouvement;
            
            \Log::info('Mouvement simple créé', [
                'compte_debit_id' => $od->compte_debit_id,
                'compte_credit_id' => $od->compte_credit_id,
                'montant' => $od->montant,
                'mouvement_id' => $mouvement->id
            ]);
        }

        // Mettre à jour l'OD
        $od->update([
            'est_comptabilise' => true,
            'comptabilise_par' => $comptable->id,
            'date_comptabilisation' => now(),
        ]);

        return $mouvements;
    });
}

    /**
     * Obtenir l'ID d'un compte par son code
     */
    private function getCompteId(string $code): ?int
    {
        $compte = PlanComptable::where('code', $code)->first();
        return $compte ? $compte->id : null;
    }

    /**
     * Obtenir le journal selon le type d'opération
     */
    private function getJournalForType(string $type): string
    {
        $journaux = [
            'MATA_BOOST' => 'COLLECTE_MATA',
            'EPARGNE_JOURNALIERE' => 'COLLECTE_EPARGNE',
            'CHARGE' => 'FRAIS',
            'DEPOT' => 'CAISSE',
            'RETRAIT' => 'CAISSE',
            'VIREMENT' => 'VIREMENT',
            'FRAIS' => 'FRAIS',
            'COMMISSION' => 'COMMISSION',
        ];

        return $journaux[$type] ?? 'DIVERS';
    }
}