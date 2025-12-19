<?php

namespace App\Services\Compte;

use App\Models\Compte;
use App\Models\CompteCommission;
use App\Models\TransactionCompte;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommissionService
{
    private const COMPTE_PRODUIT_COMMISSION = '720510000';
    private const COMPTE_ATTENTE_PRODUIT = '47120';

    /**
     * Calcule et prélève les commissions mensuelles pour tous les comptes éligibles
     */
    public function preleverCommissionsMensuelles(int $mois, int $annee): Collection
    {
        $results = collect();
        
        $accounts = Compte::with('accountType')
            ->where('statut', 'actif')
            ->get();

        foreach ($accounts as $account) {
            $result = $this->preleverCommissionMensuelleCompte($account, $mois, $annee);
            if ($result) {
                $results->push($result);
            }
        }

        return $results;
    }

    /**
     * Prélève la commission mensuelle pour un compte spécifique
     */
    public function preleverCommissionMensuelleCompte(Compte $account, int $mois, int $annee): ?CompteCommission
    {
        $accountType = $account->accountType;
        
        // Vérifier si une commission a déjà été prélevée pour ce mois
        $existante = CompteCommission::where('account_id', $account->id)
            ->where('type_commission', 'commission_mensuelle')
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        if ($existante) {
            return null;
        }

        // Calculer le total des versements du mois
        $totalVersements = $this->calculerTotalVersementsMois($account, $mois, $annee);

        // Pour les comptes MATA BOOST, pas de commission si pas de versement
        if ($accountType->isMataBoost() && $totalVersements == 0) {
            return null;
        }

        // Déterminer le montant de la commission
        $montantCommission = $accountType->getCommissionMensuelle($totalVersements);

        if ($montantCommission <= 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $montantCommission, $totalVersements, $mois, $annee) {
            $soldeDisponible = $account->solde;
            $statut = 'preleve';
            $compteProduit = self::COMPTE_PRODUIT_COMMISSION;
            $compteAttente = null;

            // Si solde insuffisant, mettre en attente
            if ($soldeDisponible < $montantCommission) {
                $statut = 'en_attente_solde';
                $compteAttente = self::COMPTE_ATTENTE_PRODUIT;
            } else {
                // Débiter le compte
                $account->decrement('solde', $montantCommission);
                $account->decrement('solde_disponible', $montantCommission);
            }

            // Créer l'enregistrement de commission
            $commission = CompteCommission::create([
                'account_id' => $account->id,
                'type_commission' => 'commission_mensuelle',
                'montant' => $montantCommission,
                'base_calcul' => $totalVersements,
                'mois' => $mois,
                'annee' => $annee,
                'statut' => $statut,
                'compte_produit' => $compteProduit,
                'compte_attente' => $compteAttente,
                'preleve_at' => $statut === 'preleve' ? now() : null,
            ]);

            // Créer la transaction si prélevé
            if ($statut === 'preleve') {
                $transaction = $this->createTransactionCommission($account, $montantCommission, 'Commission mensuelle');
                $commission->update(['transaction_id' => $transaction->id]);
            }

            return $commission;
        });
    }

    /**
     * Prélève les frais SMS mensuels
     */
    public function preleverFraisSMS(Compte $account, int $mois, int $annee): ?CompteCommission
    {
        $fraisSms = $account->accountType->frais_sms;

        if ($fraisSms <= 0) {
            return null;
        }

        $existante = CompteCommission::where('account_id', $account->id)
            ->where('type_commission', 'commission_sms')
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        if ($existante) {
            return null;
        }

        return DB::transaction(function () use ($account, $fraisSms, $mois, $annee) {
            $statut = $account->solde >= $fraisSms ? 'preleve' : 'en_attente_solde';

            if ($statut === 'preleve') {
                $account->decrement('solde', $fraisSms);
                $account->decrement('solde_disponible', $fraisSms);
            }

            $commission = CompteCommission::create([
                'account_id' => $account->id,
                'type_commission' => 'commission_sms',
                'montant' => $fraisSms,
                'mois' => $mois,
                'annee' => $annee,
                'statut' => $statut,
                'compte_produit' => self::COMPTE_PRODUIT_COMMISSION,
                'compte_attente' => $statut === 'en_attente_solde' ? self::COMPTE_ATTENTE_PRODUIT : null,
                'preleve_at' => $statut === 'preleve' ? now() : null,
            ]);

            if ($statut === 'preleve') {
                $transaction = $this->createTransactionCommission($account, $fraisSms, 'Frais SMS');
                $commission->update(['transaction_id' => $transaction->id]);
            }

            return $commission;
        });
    }

    /**
     * Régularise les commissions en attente quand le compte est approvisionné
     */
    public function regulariserCommissionsEnAttente(Compte $account): Collection
    {
        $commissionsRegularisees = collect();

        $commissionsEnAttente = CompteCommission::where('account_id', $account->id)
            ->where('statut', 'en_attente_solde')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($commissionsEnAttente as $commission) {
            if ($account->solde >= $commission->montant) {
                DB::transaction(function () use ($account, $commission, &$commissionsRegularisees) {
                    $account->decrement('solde', $commission->montant);
                    $account->decrement('solde_disponible', $commission->montant);

                    $transaction = $this->createTransactionCommission(
                        $account, 
                        $commission->montant, 
                        'Régularisation ' . $commission->type_commission
                    );

                    $commission->update([
                        'statut' => 'preleve',
                        'transaction_id' => $transaction->id,
                        'compte_attente' => null,
                        'preleve_at' => now(),
                    ]);

                    $commissionsRegularisees->push($commission);
                });

                $account->refresh();
            } else {
                break;
            }
        }

        return $commissionsRegularisees;
    }

    /**
     * Calcule le total des versements d'un compte pour un mois donné
     */
    private function calculerTotalVersementsMois(Compte $account, int $mois, int $annee): float
    {
        return TransactionCompte::where('account_id', $account->id)
            ->where('sens', 'credit')
            ->whereIn('type_transaction', ['depot', 'virement_entrant'])
            ->where('statut', 'valide')
            ->whereMonth('created_at', $mois)
            ->whereYear('created_at', $annee)
            ->sum('montant');
    }

    private function createTransactionCommission(Compte $account, float $montant, string $libelle): TransactionCompte
    {
        return TransactionCompte::create([
            'reference' => 'COM' . now()->format('YmdHis') . Str::upper(Str::random(4)),
            'account_id' => $account->id,
            'agency_id' => $account->agency_id,
            'created_by' => auth()->id() ?? 1,
            'type_transaction' => 'prelevement_commission',
            'sens' => 'debit',
            'montant' => $montant,
            'solde_avant' => $account->solde + $montant,
            'solde_apres' => $account->solde,
            'libelle' => $libelle,
            'compte_comptable_debit' => $account->numero_compte,
            'compte_comptable_credit' => self::COMPTE_PRODUIT_COMMISSION,
            'statut' => 'valide',
            'date_valeur' => now(),
        ]);
    }
}