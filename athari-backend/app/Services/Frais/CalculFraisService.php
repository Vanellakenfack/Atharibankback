<?php
// app/Services/CalculFraisService.php

namespace App\Services\Frais;

use App\Models\compte\Compte;
use App\Models\frais\FraisCommission;
use App\Models\frais\FraisApplication;
use App\Models\frais\MouvementRubriqueMata;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculFraisService
{
    /**
     * Appliquer les frais d'ouverture d'un compte
     */
    public function appliquerFraisOuverture(Compte $compte)
    {
        $fraisConfig = $compte->typeCompte->fraisCommission;
        
        if (!$fraisConfig || !$fraisConfig->frais_ouverture_actif) {
            return null;
        }
        
        DB::beginTransaction();
        
        try {
            $montantFrais = $fraisConfig->calculerFraisOuverture();
            
            // Vérifier si le solde est suffisant
            if ($compte->solde < $montantFrais) {
                // Si solde insuffisant, le compte devient débiteur
                $compte->solde -= $montantFrais;
                $compte->save();
            }
            
            // Enregistrer l'application des frais
            $fraisApplication = FraisApplication::create([
                'compte_id' => $compte->id,
                'frais_commission_id' => $fraisConfig->id,
                'type_frais' => 'ouverture',
                'montant' => $montantFrais,
                'solde_avant' => $compte->solde + $montantFrais,
                'solde_apres' => $compte->solde,
                'compte_debit' => $compte->numero_compte,
                'compte_credit' => $fraisConfig->compte_commission_paiement ?? '72100000',
                'date_application' => now(),
                'description' => 'Frais d\'ouverture de compte',
                'est_automatique' => true,
                'statut' => 'applique'
            ]);
            
            // Pour les comptes MATA, répartir sur les rubriques
            if ($compte->typeCompte->est_mata && $compte->rubriques_mata) {
                $this->repartirFraisSurRubriquesMata($compte, $montantFrais, 'ouverture');
            }
            
            DB::commit();
            
            // TODO: Générer l'écriture comptable
            
            return $fraisApplication;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur application frais ouverture: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Appliquer les commissions mensuelles
     */
    public function appliquerCommissionsMensuelles($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $debutMois = $date->copy()->startOfMonth();
        $finMois = $date->copy()->endOfMonth();
        
        // Récupérer tous les comptes actifs avec frais de commission mensuelle
        $comptes = Compte::where('statut', 'actif')
            ->whereHas('typeCompte.fraisCommission', function($q) {
                $q->where('commission_mouvement_actif', true);
            })
            ->with(['typeCompte.fraisCommission'])
            ->get();
        
        $applications = [];
        
        foreach ($comptes as $compte) {
            try {
                $fraisConfig = $compte->typeCompte->fraisCommission;
                
                // Calculer le total des versements du mois
                $totalVersements = $this->getTotalVersementsMois($compte, $debutMois, $finMois);
                
                // Calculer la commission
                $montantCommission = $fraisConfig->calculerCommissionMensuelle($totalVersements);
                
                if ($montantCommission <= 0) {
                    continue;
                }
                
                // Vérifier le solde
                if ($compte->solde >= $montantCommission) {
                    $compte->solde -= $montantCommission;
                    $compteCompteCredit = $fraisConfig->compte_produit_commission ?? '720510000';
                    $statut = 'applique';
                } else {
                    // Solde insuffisant, mettre en attente
                    $compteCompteCredit = $fraisConfig->compte_attente_produits ?? '47120';
                    $statut = 'en_attente';
                }
                
                // Enregistrer l'application
                $application = FraisApplication::create([
                    'compte_id' => $compte->id,
                    'frais_commission_id' => $fraisConfig->id,
                    'type_frais' => 'commission_mouvement',
                    'montant' => $montantCommission,
                    'solde_avant' => $compte->solde,
                    'solde_apres' => $statut === 'applique' ? $compte->solde - $montantCommission : $compte->solde,
                    'total_versements_mois' => $totalVersements,
                    'compte_debit' => $compte->numero_compte,
                    'compte_credit' => $compteCompteCredit,
                    'date_application' => $finMois,
                    'date_effet' => $finMois,
                    'description' => 'Commission mensuelle - Total versements: ' . number_format($totalVersements, 0, ',', ' '),
                    'est_automatique' => true,
                    'statut' => $statut
                ]);
                
                if ($statut === 'applique') {
                    $compte->save();
                }
                
                $applications[] = $application;
                
            } catch (\Exception $e) {
                Log::error('Erreur commission mensuelle compte ' . $compte->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        return $applications;
    }
    
    /**
     * Appliquer les commissions de retrait
     */
    public function appliquerCommissionRetrait(Compte $compte, $montantRetrait)
    {
        $fraisConfig = $compte->typeCompte->fraisCommission;
        
        if (!$fraisConfig || !$fraisConfig->commission_retrait_actif) {
            return null;
        }
        
        // Vérifier que le solde après retrait permet de payer la commission
        $commission = $fraisConfig->commission_retrait;
        $totalADebiter = $montantRetrait + $commission;
        
        if ($compte->solde < $totalADebiter) {
            throw new \Exception('Solde insuffisant pour le retrait et la commission');
        }
        
        DB::beginTransaction();
        
        try {
            $soldeAvant = $compte->solde;
            $compte->solde -= $totalADebiter;
            $compte->save();
            
            // Enregistrer la commission de retrait
            $fraisApplication = FraisApplication::create([
                'compte_id' => $compte->id,
                'frais_commission_id' => $fraisConfig->id,
                'type_frais' => 'commission_retrait',
                'montant' => $commission,
                'solde_avant' => $soldeAvant,
                'solde_apres' => $compte->solde,
                'compte_debit' => $compte->numero_compte,
                'compte_credit' => $fraisConfig->compte_produit_commission ?? '720510000',
                'date_application' => now(),
                'description' => 'Commission sur retrait de ' . number_format($montantRetrait, 0, ',', ' '),
                'est_automatique' => true,
                'statut' => 'applique'
            ]);
            
            DB::commit();
            
            return $fraisApplication;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Appliquer les commissions SMS mensuelles
     */
    public function appliquerCommissionsSMS($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $finMois = $date->copy()->endOfMonth();
        
        $comptes = Compte::where('statut', 'actif')
            ->whereHas('typeCompte.fraisCommission', function($q) {
                $q->where('commission_sms_actif', true);
            })
            ->with(['typeCompte.fraisCommission'])
            ->get();
        
        $applications = [];
        
        foreach ($comptes as $compte) {
            try {
                $fraisConfig = $compte->typeCompte->fraisCommission;
                $montantSMS = $fraisConfig->commission_sms;
                
                if ($montantSMS <= 0) {
                    continue;
                }
                
                // Vérifier le solde
                if ($compte->solde >= $montantSMS) {
                    $compte->solde -= $montantSMS;
                    $compteCompteCredit = $fraisConfig->compte_produit_commission ?? '720510000';
                    $statut = 'applique';
                } else {
                    // Solde insuffisant, mettre en attente
                    $compteCompteCredit = $fraisConfig->compte_attente_sms;
                    $statut = 'en_attente';
                }
                
                $application = FraisApplication::create([
                    'compte_id' => $compte->id,
                    'frais_commission_id' => $fraisConfig->id,
                    'type_frais' => 'commission_sms',
                    'montant' => $montantSMS,
                    'solde_avant' => $compte->solde,
                    'solde_apres' => $statut === 'applique' ? $compte->solde - $montantSMS : $compte->solde,
                    'compte_debit' => $compte->numero_compte,
                    'compte_credit' => $compteCompteCredit,
                    'date_application' => $finMois,
                    'date_effet' => $finMois,
                    'description' => 'Commission SMS mensuelle',
                    'est_automatique' => true,
                    'statut' => $statut
                ]);
                
                if ($statut === 'applique') {
                    $compte->save();
                }
                
                $applications[] = $application;
                
            } catch (\Exception $e) {
                Log::error('Erreur commission SMS compte ' . $compte->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        return $applications;
    }
    
    /**
     * Calculer et appliquer les intérêts créditeurs
     */
    public function calculerInteretsCrediteurs($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        $comptes = Compte::where('statut', 'actif')
            ->whereHas('typeCompte.fraisCommission', function($q) {
                $q->where('interets_actifs', true);
            })
            ->with(['typeCompte.fraisCommission'])
            ->get();
        
        $applications = [];
        
        foreach ($comptes as $compte) {
            try {
                $fraisConfig = $compte->typeCompte->fraisCommission;
                
                // Vérifier si c'est le moment de calculer les intérêts
                if ($fraisConfig->frequence_calcul_interet === 'journalier') {
                    $interets = $fraisConfig->calculerInteretsJournaliers($compte->solde);
                    
                    if ($interets > 0) {
                        $compte->solde += $interets;
                        $compte->save();
                        
                        $application = FraisApplication::create([
                            'compte_id' => $compte->id,
                            'frais_commission_id' => $fraisConfig->id,
                            'type_frais' => 'interet',
                            'montant' => $interets,
                            'solde_avant' => $compte->solde - $interets,
                            'solde_apres' => $compte->solde,
                            'date_debut_periode' => $date,
                            'date_fin_periode' => $date,
                            'date_application' => $date,
                            'description' => 'Intérêts créditeurs journaliers',
                            'est_automatique' => true,
                            'statut' => 'applique'
                        ]);
                        
                        $applications[] = $application;
                    }
                }
                
            } catch (\Exception $e) {
                Log::error('Erreur calcul intérêts compte ' . $compte->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        return $applications;
    }
    
    /**
     * Appliquer les frais de déblocage pour un compte bloqué
     */
    public function appliquerFraisDeblocage(Compte $compte, $estAnticipe = false)
    {
        $fraisConfig = $compte->typeCompte->fraisCommission;
        
        if (!$fraisConfig || !$fraisConfig->frais_deblocage_actif) {
            return null;
        }
        
        DB::beginTransaction();
        
        try {
            $montantFrais = $fraisConfig->frais_deblocage;
            
            // Ajouter pénalité si retrait anticipé
            if ($estAnticipe && $fraisConfig->penalite_actif) {
                $penalite = $compte->solde * ($fraisConfig->penalite_retrait_anticipe / 100);
                $montantFrais += $penalite;
            }
            
            // Vérifier solde
            if ($compte->solde < $montantFrais) {
                throw new \Exception('Solde insuffisant pour les frais de déblocage');
            }
            
            $soldeAvant = $compte->solde;
            $compte->solde -= $montantFrais;
            $compte->save();
            
            $typeFrais = $estAnticipe ? 'penalite' : 'deblocage';
            $description = $estAnticipe 
                ? 'Frais de déblocage anticipé + pénalité' 
                : 'Frais de déblocage à échéance';
            
            $fraisApplication = FraisApplication::create([
                'compte_id' => $compte->id,
                'frais_commission_id' => $fraisConfig->id,
                'type_frais' => $typeFrais,
                'montant' => $montantFrais,
                'solde_avant' => $soldeAvant,
                'solde_apres' => $compte->solde,
                'compte_debit' => $compte->numero_compte,
                'compte_credit' => $fraisConfig->compte_commission_paiement ?? '72100000',
                'date_application' => now(),
                'description' => $description,
                'est_automatique' => true,
                'statut' => 'applique'
            ]);
            
            DB::commit();
            
            return $fraisApplication;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Vérifier si un retrait anticipé est autorisé
     */
    public function verifierRetraitAnticipe(Compte $compte)
    {
        $fraisConfig = $compte->typeCompte->fraisCommission;
        
        if (!$fraisConfig) {
            return false;
        }
        
        // Vérifier si le compte est bloqué
        if (!$compte->duree_blocage_mois) {
            return true; // Pas un compte bloqué
        }
        
        // Vérifier si l'échéance est atteinte
        $dateOuverture = Carbon::parse($compte->date_ouverture);
        $dateEcheance = $dateOuverture->addMonths($compte->duree_blocage_mois);
        
        if (Carbon::now()->gte($dateEcheance)) {
            return true; // Échéance atteinte
        }
        
        // Retrait anticipé : vérifier autorisation
        return $fraisConfig->retrait_anticipe_autorise;
    }
    
    /**
     * Répartir les frais sur les rubriques MATA
     */
    private function repartirFraisSurRubriquesMata(Compte $compte, $montant, $type)
    {
        $rubriques = json_decode($compte->rubriques_mata, true);
        
        if (empty($rubriques)) {
            return;
        }
        
        // Répartir équitablement sur toutes les rubriques
        $montantParRubrique = $montant / count($rubriques);
        
        foreach ($rubriques as $rubrique) {
            MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubrique,
                'montant' => $montantParRubrique,
                'type_mouvement' => 'commission',
                'description' => "Frais d'{$type} répartis sur la rubrique {$rubrique}",
                'solde_rubrique' => 0, // À calculer en fonction des mouvements existants
                'solde_global' => $compte->solde
            ]);
        }
    }
    
    /**
     * Obtenir le total des versements du mois
     */
    private function getTotalVersementsMois(Compte $compte, $debutMois, $finMois)
    {
        // Implémenter la logique pour récupérer les versements du mois
        // Depuis la table des transactions ou mouvements
        return 0; // À implémenter
    }
}