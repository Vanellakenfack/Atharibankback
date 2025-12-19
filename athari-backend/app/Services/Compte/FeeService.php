<?php
// app/Services/Compte/FeeService.php

namespace App\Services\Compte;

use App\Models\Compte;
use App\Models\TypesCompte;
use App\Models\TransactionCompte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeeService
{
    private const COMPTE_COMMISSION_INSTRUMENT = '721000001';
    private const COMPTE_CAISSE = '571000001';

    /**
     * Prélève les frais d'ouverture automatiquement
     */
    public function prelevesFraisOuverture(Compte $account, TypesCompte $accountType): void
    {
        $fraisOuverture = $accountType->frais_ouverture;
        $fraisCarnet = $accountType->frais_carnet;
        $totalFrais = $fraisOuverture + $fraisCarnet;

        if ($totalFrais <= 0) {
            return;
        }

        DB::transaction(function () use ($account, $totalFrais, $fraisCarnet, $fraisOuverture) {
            // Le compte devient débiteur (négatif) en attendant l'approvisionnement
            $account->update([
                'solde' => -$totalFrais,
                'solde_disponible' => -$totalFrais,
            ]);

            // Enregistrement de la transaction de frais de carnet
            if ($fraisCarnet > 0) {
                $this->createTransaction($account, [
                    'type_transaction' => 'prelevement_frais',
                    'sens' => 'debit',
                    'montant' => $fraisCarnet,
                    'solde_avant' => 0,
                    'solde_apres' => -$fraisCarnet,
                    'libelle' => 'Prélèvement frais de carnet',
                    'compte_comptable_debit' => $account->numero_compte,
                    'compte_comptable_credit' => self::COMPTE_COMMISSION_INSTRUMENT,
                    'statut' => 'valide',
                ]);
            }

            // Enregistrement de la transaction de frais d'ouverture
            if ($fraisOuverture > 0) {
                $this->createTransaction($account, [
                    'type_transaction' => 'prelevement_frais',
                    'sens' => 'debit',
                    'montant' => $fraisOuverture,
                    'solde_avant' => -$fraisCarnet,
                    'solde_apres' => -$totalFrais,
                    'libelle' => 'Prélèvement frais d\'ouverture',
                    'compte_comptable_debit' => $account->numero_compte,
                    'compte_comptable_credit' => self::COMPTE_COMMISSION_INSTRUMENT,
                    'statut' => 'valide',
                ]);
            }
        });
    }

    /**
     * Prélève les frais de retrait
     */
    public function prelevesFraisRetrait(Compte $account): ?TransactionCompte
    {
        $fraisRetrait = $account->accountType->frais_retrait;

        if ($fraisRetrait <= 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $fraisRetrait) {
            $soldeAvant = $account->solde;
            $account->decrement('solde', $fraisRetrait);
            $account->decrement('solde_disponible', $fraisRetrait);

            return $this->createTransaction($account, [
                'type_transaction' => 'prelevement_frais',
                'sens' => 'debit',
                'montant' => $fraisRetrait,
                'solde_avant' => $soldeAvant,
                'solde_apres' => $account->solde,
                'libelle' => 'Frais de retrait',
                'statut' => 'valide',
            ]);
        });
    }

    /**
     * Prélève les frais de déblocage
     */
    public function prelevesFraisDeblocage(Compte $account, bool $anticipe = false): float
    {
        $fraisDeblocage = $account->accountType->frais_deblocage;
        $penalite = 0;

        if ($anticipe && $account->accountType->penalite_retrait_anticipe > 0) {
            $penalite = $account->solde * ($account->accountType->penalite_retrait_anticipe / 100);
        }

        $totalFrais = $fraisDeblocage + $penalite;

        if ($totalFrais > 0) {
            DB::transaction(function () use ($account, $totalFrais, $fraisDeblocage, $penalite) {
                $soldeAvant = $account->solde;
                $account->decrement('solde', $totalFrais);
                $account->decrement('solde_disponible', $totalFrais);

                $libelle = 'Frais de déblocage';
                if ($penalite > 0) {
                    $libelle .= sprintf(' + pénalité retrait anticipé (%.0f FCFA)', $penalite);
                }

                $this->createTransaction($account, [
                    'type_transaction' => $penalite > 0 ? 'penalite' : 'prelevement_frais',
                    'sens' => 'debit',
                    'montant' => $totalFrais,
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $account->solde,
                    'libelle' => $libelle,
                    'statut' => 'valide',
                ]);
            });
        }

        return $totalFrais;
    }

    /**
     * Crée une transaction
     */
    private function createTransaction(Compte $account, array $data): TransactionCompte
    {
        return TransactionCompte::create(array_merge([
            'reference' => $this->generateReference(),
            'account_id' => $account->id,
            'agency_id' => $account->agency_id,
            'created_by' => auth()->id() ?? 1,
            'date_valeur' => now(),
        ], $data));
    }

    private function generateReference(): string
    {
        return 'TRX' . now()->format('YmdHis') . Str::upper(Str::random(4));
    }
}