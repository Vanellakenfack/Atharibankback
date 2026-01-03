<?php

namespace App\Services\Frais;

use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\frais\FraisApplication;
use App\Models\frais\CalculInteret;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FraisCommissionService
{
    /**
     * Appliquer les frais d'ouverture
     */
    public function appliquerFraisOuverture(Compte $compte, $userId = null): array
    {
        $typeCompte = $compte->typeCompte;
        
        if (!$typeCompte->frais_ouverture_actif) {
            return [
                'success' => false,
                'message' => 'Frais d\'ouverture non activés pour ce type de compte'
            ];
        }

        $montantFrais = $typeCompte->calculerFraisOuverture();

        DB::beginTransaction();
        try {
            // Créer l'application de frais
            $fraisApplication = FraisApplication::creerFraisOuverture(
                $compte,
                $typeCompte,
                $userId
            );

            // Débiter le compte (solde devient négatif)
            $compte->solde -= $montantFrais;
            $compte->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Frais d\'ouverture appliqués avec succès',
                'frais_application' => $fraisApplication,
                'montant_preleve' => $montantFrais,
                'nouveau_solde' => $compte->solde,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'application des frais: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prélever la commission mensuelle
     */
    public function preleverCommissionMensuelle(
        Compte $compte,
        float $totalVersements,
        string $periode,
        $userId = null
    ): array {
        $typeCompte = $compte->typeCompte;
        
        if (!$typeCompte->commission_mensuelle_actif) {
            return [
                'success' => false,
                'message' => 'Commission mensuelle non activée'
            ];
        }

        $montantCommission = $typeCompte->calculerCommissionMensuelle($totalVersements);

        DB::beginTransaction();
        try {
            // Si solde suffisant
            if ($compte->solde >= $montantCommission) {
                // Créer et appliquer
                $fraisApplication = FraisApplication::creerCommissionMensuelle(
                    $compte,
                    $typeCompte,
                    $montantCommission,
                    $totalVersements,
                    $periode,
                    $userId
                );

                // Débiter
                $compte->solde -= $montantCommission;
                $compte->save();

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Commission mensuelle prélevée',
                    'frais_application' => $fraisApplication,
                    'montant_preleve' => $montantCommission,
                    'statut' => 'APPLIQUE',
                ];
            } else {
                // Mettre en attente
                $fraisApplication = FraisApplication::create([
                    'compte_id' => $compte->id,
                    'type_compte_id' => $typeCompte->id,
                    'type_frais' => 'COMMISSION_MENSUELLE',
                    'montant_base' => $totalVersements,
                    'montant_frais' => $montantCommission,
                    'chapitre_debit_id' => $compte->plan_comptable_id,
                    'chapitre_credit_id' => $typeCompte->compte_attente_produits_id,
                    'statut' => 'EN_ATTENTE',
                    'date_application' => now(),
                    'date_valeur' => now(),
                    'periode_reference' => $periode,
                    'applique_par' => $userId,
                    'details' => [
                        'total_versements' => $totalVersements,
                        'solde_insuffisant' => true,
                        'solde_actuel' => $compte->solde,
                    ],
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Commission mise en attente (solde insuffisant)',
                    'frais_application' => $fraisApplication,
                    'montant_en_attente' => $montantCommission,
                    'statut' => 'EN_ATTENTE',
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prélever commission de retrait
     */
    public function preleverCommissionRetrait(Compte $compte, $userId = null): array
    {
        $typeCompte = $compte->typeCompte;
        
        if (!$typeCompte->commission_retrait_actif) {
            return ['success' => true, 'montant_preleve' => 0];
        }

        $montantCommission = $typeCompte->commission_retrait;

        DB::beginTransaction();
        try {
            $fraisApplication = FraisApplication::create([
                'compte_id' => $compte->id,
                'type_compte_id' => $typeCompte->id,
                'type_frais' => 'COMMISSION_RETRAIT',
                'montant_frais' => $montantCommission,
                'chapitre_debit_id' => $compte->plan_comptable_id,
                'chapitre_credit_id' => $typeCompte->chapitre_commission_retrait_id,
                'numero_piece' => FraisApplication::genererNumeroPiece('COMMISSION_RETRAIT', now()),
                'statut' => 'APPLIQUE',
                'date_application' => now(),
                'date_valeur' => now(),
                'applique_par' => $userId,
            ]);

            $compte->solde -= $montantCommission;
            $compte->save();

            DB::commit();

            return [
                'success' => true,
                'frais_application' => $fraisApplication,
                'montant_preleve' => $montantCommission,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Prélever commission SMS
     */
    public function preleverCommissionSms(Compte $compte, string $periode, $userId = null): array
    {
        $typeCompte = $compte->typeCompte;
        
        if (!$typeCompte->commission_sms_actif) {
            return ['success' => false, 'message' => 'Commission SMS non activée'];
        }

        $montantCommission = $typeCompte->commission_sms;

        DB::beginTransaction();
        try {
            if ($compte->solde >= $montantCommission) {
                $statut = 'APPLIQUE';
                $chapitreCredit = $typeCompte->chapitre_commission_sms_id;
                
                $compte->solde -= $montantCommission;
                $compte->save();
            } else {
                $statut = 'EN_ATTENTE';
                $chapitreCredit = $typeCompte->compte_attente_produits_id;
            }

            $fraisApplication = FraisApplication::create([
                'compte_id' => $compte->id,
                'type_compte_id' => $typeCompte->id,
                'type_frais' => 'COMMISSION_SMS',
                'montant_frais' => $montantCommission,
                'chapitre_debit_id' => $compte->plan_comptable_id,
                'chapitre_credit_id' => $chapitreCredit,
                'statut' => $statut,
                'date_application' => now(),
                'date_valeur' => now(),
                'periode_reference' => $periode,
                'applique_par' => $userId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'frais_application' => $fraisApplication,
                'montant_preleve' => $statut === 'APPLIQUE' ? $montantCommission : 0,
                'statut' => $statut,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupérer les produits en attente quand le solde le permet
     */
    public function recupererProduitsAttente(Compte $compte): array
    {
        $fraisEnAttente = FraisApplication::where('compte_id', $compte->id)
            ->where('statut', 'EN_ATTENTE')
            ->orderBy('date_application')
            ->get();

        if ($fraisEnAttente->isEmpty()) {
            return ['success' => true, 'message' => 'Aucun frais en attente'];
        }

        $totalRecupere = 0;
        $recuperes = [];

        DB::beginTransaction();
        try {
            foreach ($fraisEnAttente as $frais) {
                if ($compte->solde >= $frais->montant_frais) {
                    // Appliquer
                    $compte->solde -= $frais->montant_frais;
                    $frais->statut = 'APPLIQUE';
                    
                    // Changer chapitre crédit du compte attente vers le vrai chapitre
                    $typeCompte = $compte->typeCompte;
                    $frais->chapitre_credit_id = match($frais->type_frais) {
                        'COMMISSION_MENSUELLE' => $typeCompte->chapitre_commission_mensuelle_id,
                        'COMMISSION_SMS' => $typeCompte->chapitre_commission_sms_id,
                        default => $frais->chapitre_credit_id,
                    };
                    
                    $frais->save();
                    
                    $totalRecupere += $frais->montant_frais;
                    $recuperes[] = $frais;
                }
            }

            $compte->save();
            DB::commit();

            return [
                'success' => true,
                'message' => count($recuperes) . ' frais récupérés',
                'total_recupere' => $totalRecupere,
                'frais_recuperes' => $recuperes,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculer les intérêts journaliers pour tous les comptes éligibles
     */
    public function calculerInteretsJournaliers(Carbon $date): array
    {
        // Récupérer tous les types de comptes avec intérêts actifs
        $typesComptes = TypeCompte::where('interets_actifs', true)->get();
        
        $resultats = [];
        $totalInterets = 0;

        foreach ($typesComptes as $typeCompte) {
            // Comptes de ce type
            $comptes = Compte::where('type_compte_id', $typeCompte->id)
                ->where('statut', 'ACTIF')
                ->get();

            foreach ($comptes as $compte) {
                // Solde à 12h
                $solde = $this->getSoldeAt($compte, $date->copy()->setTime(12, 0));
                
                if ($solde > 0) {
                    $calcul = CalculInteret::creerCalculJournalier(
                        $compte,
                        $typeCompte,
                        $date,
                        $solde
                    );

                    if ($calcul) {
                        $resultats[] = $calcul;
                        $totalInterets += $calcul->interets_nets;
                    }
                }
            }
        }

        return [
            'success' => true,
            'date' => $date->format('Y-m-d'),
            'nombre_calculs' => count($resultats),
            'total_interets' => $totalInterets,
            'calculs' => $resultats,
        ];
    }

    /**
     * Obtenir le solde d'un compte à une date/heure précise
     */
    private function getSoldeAt(Compte $compte, Carbon $dateTime): float
    {
        // Simplification: retourne le solde actuel
        // En production: calculer via l'historique des opérations
        return $compte->solde;
    }

    /**
     * Calculer pénalité de retrait anticipé
     */
    public function calculerPenaliteRetrait(Compte $compte, float $montant): array
    {
        $typeCompte = $compte->typeCompte;
        
        if (!$typeCompte->penalite_actif) {
            return ['success' => true, 'penalite' => 0];
        }

        $penalite = $typeCompte->calculerPenaliteRetrait($montant);

        return [
            'success' => true,
            'penalite' => $penalite,
            'taux' => $typeCompte->penalite_retrait_anticipe,
            'montant_base' => $montant,
        ];
    }
}