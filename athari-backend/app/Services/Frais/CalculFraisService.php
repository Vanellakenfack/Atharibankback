<?php

namespace App\Services\Frais;

use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\compte\frais\ParametrageFrais;
use App\Models\compte\frais\FraisApplique;
use App\Models\compte\frais\RegleCalcul;
use App\Models\chapitre\PlanComptable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculFraisService
{
    /**
     * Appliquer les frais mensuels pour tous les comptes
     */
    public function appliquerFraisMensuels()
    {
        $dateApplication = now()->endOfMonth();
        $jourActuel = now()->day;
        
        // Récupérer tous les frais mensuels actifs
        $fraisMensuels = ParametrageFrais::where('periodicite', 'MENSUEL')
            ->actif()
            ->get();

        foreach ($fraisMensuels as $frais) {
            // Vérifier si c'est le bon jour pour ce frais
            if ($frais->jour_prelevement && $frais->jour_prelevement != $jourActuel) {
                continue;
            }

            // Récupérer les comptes concernés
            $comptesQuery = Compte::actif();
            
            if ($frais->type_compte_id) {
                $comptesQuery->where('type_compte_id', $frais->type_compte_id);
            }
            
            if ($frais->plan_comptable_id) {
                $comptesQuery->where('plan_comptable_id', $frais->plan_comptable_id);
            }

            $comptes = $comptesQuery->get();

            foreach ($comptes as $compte) {
                $this->appliquerFraisCompte($compte, $frais, $dateApplication);
            }
        }

        Log::info('Frais mensuels appliqués pour ' . $dateApplication->format('Y-m-d'));
    }

    /**
     * Appliquer un frais spécifique à un compte
     */
    private function appliquerFraisCompte(Compte $compte, ParametrageFrais $frais, Carbon $dateApplication)
    {
        try {
            DB::beginTransaction();

            // Calculer la base de calcul selon le type
            $baseCalcul = $this->calculerBase($compte, $frais);
            
            // Calculer le montant
            $montant = $frais->calculerMontant($baseCalcul);

            if ($montant <= 0) {
                DB::rollBack();
                return;
            }

            // Créer le frais appliqué
            $fraisApplique = FraisApplique::create([
                'compte_id' => $compte->id,
                'parametrage_frais_id' => $frais->id,
                'date_application' => $dateApplication,
                'montant_calcule' => $montant,
                'base_calcul_valeur' => $baseCalcul,
                'methode_calcul' => $frais->base_calcul,
                'statut' => 'A_PRELEVER',
                'compte_produit_id' => $frais->compte_produit_id,
                'compte_client_id' => $compte->plan_comptable_id,
                'reference_comptable' => 'FRAIS-' . now()->format('Ym') . '-' . $compte->id,
            ]);

            // Appliquer le frais
            $fraisApplique->appliquer();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur application frais: ' . $e->getMessage(), [
                'compte_id' => $compte->id,
                'frais_id' => $frais->id,
            ]);
        }
    }

    /**
     * Calculer la base de calcul selon le type de frais
     */
    private function calculerBase(Compte $compte, ParametrageFrais $frais): float
    {
        switch ($frais->base_calcul) {
            case 'POURCENTAGE_SOLDE':
                return $compte->solde;
                
            case 'POURCENTAGE_VERSEMENT':
                // Récupérer les versements du mois
                $debutMois = now()->startOfMonth();
                $finMois = now()->endOfMonth();
                
                return DB::table('operations')
                    ->where('compte_id', $compte->id)
                    ->where('type_operation', 'VERSEMENT')
                    ->whereBetween('date_operation', [$debutMois, $finMois])
                    ->sum('montant');
                    
            case 'SEUIL_COLLECTE':
                // Pour les comptes de collecte
                $debutMois = now()->startOfMonth();
                $finMois = now()->endOfMonth();
                
                return DB::table('operations')
                    ->where('compte_id', $compte->id)
                    ->where('type_operation', 'VERSEMENT')
                    ->whereBetween('date_operation', [$debutMois, $finMois])
                    ->sum('montant');
                    
            default:
                return 0;
        }
    }

    /**
     * Appliquer les frais de SMS pour tous les comptes
     */
    public function appliquerFraisSMS()
    {
        $fraisSMS = ParametrageFrais::where('type_frais', 'SMS')
            ->where('periodicite', 'MENSUEL')
            ->actif()
            ->get();

        foreach ($fraisSMS as $frais) {
            $comptes = Compte::whereHas('typeCompte', function ($query) use ($frais) {
                if ($frais->type_compte_id) {
                    $query->where('id', $frais->type_compte_id);
                }
            })->actif()->get();

            foreach ($comptes as $compte) {
                $this->appliquerFraisCompte($compte, $frais, now()->endOfMonth());
            }
        }
    }

    /**
     * Vérifier et appliquer les frais de retrait
     */
    public function verifierFraisRetrait(Compte $compte, float $montantRetrait)
    {
        $fraisRetrait = ParametrageFrais::where('type_frais', 'RETRAIT')
            ->where(function ($query) use ($compte) {
                $query->where('type_compte_id', $compte->type_compte_id)
                    ->orWhereNull('type_compte_id');
            })
            ->actif()
            ->first();

        if (!$fraisRetrait) {
            return true; // Pas de frais de retrait
        }

        // Vérifier si le solde est suffisant pour le retrait + frais
        $montantFrais = $fraisRetrait->calculerMontant($montantRetrait);
        $total = $montantRetrait + $montantFrais;

        if ($compte->solde < $total && $fraisRetrait->bloquer_operation) {
            return false; // Bloquer l'opération
        }

        // Créer le frais de retrait
        $fraisApplique = FraisApplique::create([
            'compte_id' => $compte->id,
            'parametrage_frais_id' => $fraisRetrait->id,
            'date_application' => now(),
            'montant_calcule' => $montantFrais,
            'base_calcul_valeur' => $montantRetrait,
            'methode_calcul' => $fraisRetrait->base_calcul,
            'statut' => 'A_PRELEVER',
            'compte_produit_id' => $fraisRetrait->compte_produit_id,
            'compte_client_id' => $compte->plan_comptable_id,
            'reference_comptable' => 'RETRAIT-' . now()->format('YmdHis'),
        ]);

        $fraisApplique->appliquer();

        return true;
    }

    /**
     * Appliquer les frais d'ouverture de compte
     */
    public function appliquerFraisOuverture(Compte $compte)
    {
        $fraisOuverture = ParametrageFrais::where('type_frais', 'OUVERTURE')
            ->where(function ($query) use ($compte) {
                $query->where('type_compte_id', $compte->type_compte_id)
                    ->orWhereNull('type_compte_id');
            })
            ->actif()
            ->first();

        if ($fraisOuverture) {
            $this->appliquerFraisCompte($compte, $fraisOuverture, now());
        }
    }

    /**
     * Gérer les frais de déblocage anticipé
     */
    public function fraisDeblocageAnticipe(Compte $compte, bool $estAnticipe = false)
    {
        $typeFrais = $estAnticipe ? 'PENALITE' : 'DEBLOCAGE';
        
        $frais = ParametrageFrais::where('type_frais', $typeFrais)
            ->where(function ($query) use ($compte) {
                $query->where('type_compte_id', $compte->type_compte_id)
                    ->orWhereNull('type_compte_id');
            })
            ->actif()
            ->first();

        if ($frais) {
            $montant = $frais->calculerMontant($compte->solde);
            
            if ($estAnticipe && $frais->taux_pourcentage) {
                $montant += $compte->solde * ($frais->taux_pourcentage / 100);
            }

            $this->appliquerFraisCompte($compte, $frais, now());
        }
    }

    /**
     * Relancer les frais en attente
     */
    public function relancerFraisAttente()
    {
        $fraisEnAttente = FraisApplique::where('statut', 'EN_ATTENTE')
            ->where('date_application', '<', now()->subDays(7))
            ->with('compte')
            ->get();

        foreach ($fraisEnAttente as $frais) {
            if ($frais->compte->solde >= $frais->montant_calcule) {
                $frais->appliquer();
            }
        }
    }
}