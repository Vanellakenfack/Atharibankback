<?php

namespace App\Services\Frais;

use App\Models\compte\Compte;
use App\Models\frais\MouvementRubriqueMata;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GestionRubriqueMataService
{
    /**
     * Enregistrer un versement sur une rubrique MATA
     */
    public function enregistrerVersement(Compte $compte, $rubrique, $montant, $reference = null)
    {
        DB::beginTransaction();
        
        try {
            // Vérifier que le compte est MATA
            if (!$compte->typeCompte->est_mata) {
                throw new \Exception('Ce compte n\'est pas un compte MATA');
            }
            
            // Vérifier que la rubrique existe dans le compte
            $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
            if (!in_array($rubrique, $rubriques)) {
                throw new \Exception("La rubrique {$rubrique} n'est pas configurée pour ce compte");
            }
            
            // Calculer le solde actuel de la rubrique
            $soldeRubriqueActuel = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubrique);
            $nouveauSoldeRubrique = $soldeRubriqueActuel + $montant;
            
            // Mettre à jour le solde global du compte
            $compte->solde += $montant;
            $compte->save();
            
            // Enregistrer le mouvement
            $mouvement = MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubrique,
                'montant' => $montant,
                'solde_rubrique' => $nouveauSoldeRubrique,
                'solde_global' => $compte->solde,
                'type_mouvement' => 'versement',
                'reference_operation' => $reference,
                'description' => "Versement de " . number_format($montant, 0, ',', ' ') . " FCFA sur la rubrique {$rubrique}"
            ]);
            
            DB::commit();
            
            return $mouvement;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur versement rubrique MATA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Enregistrer un retrait sur une rubrique MATA
     */
    public function enregistrerRetrait(Compte $compte, $rubrique, $montant, $reference = null)
    {
        DB::beginTransaction();
        
        try {
            // Vérifier que le compte est MATA
            if (!$compte->typeCompte->est_mata) {
                throw new \Exception('Ce compte n\'est pas un compte MATA');
            }
            
            // Vérifier que la rubrique existe dans le compte
            $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
            if (!in_array($rubrique, $rubriques)) {
                throw new \Exception("La rubrique {$rubrique} n'est pas configurée pour ce compte");
            }
            
            // Vérifier le solde de la rubrique
            $soldeRubriqueActuel = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubrique);
            if ($soldeRubriqueActuel < $montant) {
                throw new \Exception("Solde insuffisant sur la rubrique {$rubrique}");
            }
            
            // Calculer le nouveau solde
            $nouveauSoldeRubrique = $soldeRubriqueActuel - $montant;
            
            // Vérifier le solde global du compte
            if ($compte->solde < $montant) {
                throw new \Exception("Solde global insuffisant");
            }
            
            // Mettre à jour le solde global
            $compte->solde -= $montant;
            $compte->save();
            
            // Enregistrer le mouvement
            $mouvement = MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubrique,
                'montant' => $montant,
                'solde_rubrique' => $nouveauSoldeRubrique,
                'solde_global' => $compte->solde,
                'type_mouvement' => 'retrait',
                'reference_operation' => $reference,
                'description' => "Retrait de " . number_format($montant, 0, ',', ' ') . " FCFA sur la rubrique {$rubrique}"
            ]);
            
            DB::commit();
            
            return $mouvement;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur retrait rubrique MATA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Enregistrer une commission sur une rubrique MATA
     */
    public function enregistrerCommission(Compte $compte, $rubrique, $montant, $typeCommission, $reference = null)
    {
        DB::beginTransaction();
        
        try {
            // Vérifier que le compte est MATA
            if (!$compte->typeCompte->est_mata) {
                throw new \Exception('Ce compte n\'est pas un compte MATA');
            }
            
            // Vérifier que la rubrique existe dans le compte
            $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
            if (!in_array($rubrique, $rubriques)) {
                throw new \Exception("La rubrique {$rubrique} n'est pas configurée pour ce compte");
            }
            
            // Calculer le solde actuel de la rubrique
            $soldeRubriqueActuel = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubrique);
            
            // Pour une commission, on diminue le solde de la rubrique
            $nouveauSoldeRubrique = $soldeRubriqueActuel - $montant;
            
            // Vérifier que le solde de la rubrique ne devient pas négatif
            if ($nouveauSoldeRubrique < 0) {
                throw new \Exception("La commission dépasse le solde de la rubrique {$rubrique}");
            }
            
            // Mettre à jour le solde global du compte (les commissions réduisent le solde global)
            $compte->solde -= $montant;
            $compte->save();
            
            // Enregistrer le mouvement
            $mouvement = MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubrique,
                'montant' => $montant,
                'solde_rubrique' => $nouveauSoldeRubrique,
                'solde_global' => $compte->solde,
                'type_mouvement' => 'commission',
                'reference_operation' => $reference,
                'description' => "Commission {$typeCommission} de " . number_format($montant, 0, ',', ' ') . " FCFA sur la rubrique {$rubrique}"
            ]);
            
            DB::commit();
            
            return $mouvement;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur commission rubrique MATA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Répartir un montant sur toutes les rubriques MATA
     */
    public function repartirMontantSurRubriques(Compte $compte, $montantTotal, $typeOperation, $reference = null)
    {
        DB::beginTransaction();
        
        try {
            // Vérifier que le compte est MATA
            if (!$compte->typeCompte->est_mata) {
                throw new \Exception('Ce compte n\'est pas un compte MATA');
            }
            
            $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
            
            if (empty($rubriques)) {
                throw new \Exception('Aucune rubrique configurée pour ce compte MATA');
            }
            
            // Répartir équitablement
            $montantParRubrique = $montantTotal / count($rubriques);
            $mouvements = [];
            
            foreach ($rubriques as $rubrique) {
                $soldeRubriqueActuel = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubrique);
                
                if ($typeOperation === 'versement') {
                    $nouveauSoldeRubrique = $soldeRubriqueActuel + $montantParRubrique;
                } else {
                    $nouveauSoldeRubrique = $soldeRubriqueActuel - $montantParRubrique;
                    
                    // Vérifier que le solde ne devient pas négatif
                    if ($nouveauSoldeRubrique < 0) {
                        throw new \Exception("La répartition dépasse le solde de la rubrique {$rubrique}");
                    }
                }
                
                $mouvement = MouvementRubriqueMata::create([
                    'compte_id' => $compte->id,
                    'rubrique' => $rubrique,
                    'montant' => $montantParRubrique,
                    'solde_rubrique' => $nouveauSoldeRubrique,
                    'solde_global' => $compte->solde, // Note: le solde global sera mis à jour après
                    'type_mouvement' => $typeOperation,
                    'reference_operation' => $reference,
                    'description' => "Répartition " . number_format($montantParRubrique, 0, ',', ' ') . " FCFA sur la rubrique {$rubrique}"
                ]);
                
                $mouvements[] = $mouvement;
            }
            
            // Mettre à jour le solde global
            if ($typeOperation === 'versement') {
                $compte->solde += $montantTotal;
            } else {
                $compte->solde -= $montantTotal;
            }
            
            $compte->save();
            
            // Mettre à jour les soldes globaux dans les mouvements
            foreach ($mouvements as $mouvement) {
                $mouvement->update(['solde_global' => $compte->solde]);
            }
            
            DB::commit();
            
            return $mouvements;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur répartition rubriques MATA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtenir le récapitulatif des soldes par rubrique
     */
    public function getRecapitulatifRubriques(Compte $compte)
    {
        $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
        $recapitulatif = [];
        
        foreach ($rubriques as $rubrique) {
            $solde = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubrique);
            $dernierMouvement = MouvementRubriqueMata::getDernierMouvement($compte->id, $rubrique);
            
            $recapitulatif[] = [
                'rubrique' => $rubrique,
                'solde' => $solde,
                'pourcentage' => $compte->solde > 0 ? ($solde / $compte->solde) * 100 : 0,
                'dernier_mouvement' => $dernierMouvement ? $dernierMouvement->created_at->format('d/m/Y H:i') : null,
                'dernier_type' => $dernierMouvement ? $dernierMouvement->type_mouvement : null
            ];
        }
        
        return [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'solde_global' => $compte->solde,
            'rubriques' => $recapitulatif,
            'date_extrait' => now()->format('d/m/Y H:i')
        ];
    }
    
    /**
     * Obtenir l'historique des mouvements d'une rubrique
     */
    public function getHistoriqueRubrique(Compte $compte, $rubrique, $limit = 50)
    {
        return MouvementRubriqueMata::where('compte_id', $compte->id)
            ->where('rubrique', $rubrique)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Transférer des fonds entre rubriques
     */
    public function transfererEntreRubriques(Compte $compte, $rubriqueSource, $rubriqueDestination, $montant, $reference = null)
    {
        DB::beginTransaction();
        
        try {
            // Vérifier que les rubriques existent
            $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
            
            if (!in_array($rubriqueSource, $rubriques) || !in_array($rubriqueDestination, $rubriques)) {
                throw new \Exception('Une ou plusieurs rubriques n\'existent pas dans ce compte');
            }
            
            if ($rubriqueSource === $rubriqueDestination) {
                throw new \Exception('Impossible de transférer vers la même rubrique');
            }
            
            // Vérifier le solde de la rubrique source
            $soldeSource = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubriqueSource);
            if ($soldeSource < $montant) {
                throw new \Exception("Solde insuffisant sur la rubrique source {$rubriqueSource}");
            }
            
            // Calculer les nouveaux soldes
            $nouveauSoldeSource = $soldeSource - $montant;
            $soldeDestination = MouvementRubriqueMata::getSoldeRubrique($compte->id, $rubriqueDestination);
            $nouveauSoldeDestination = $soldeDestination + $montant;
            
            // Enregistrer le débit sur la source
            MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubriqueSource,
                'montant' => $montant,
                'solde_rubrique' => $nouveauSoldeSource,
                'solde_global' => $compte->solde,
                'type_mouvement' => 'retrait',
                'reference_operation' => $reference,
                'description' => "Transfert vers {$rubriqueDestination}"
            ]);
            
            // Enregistrer le crédit sur la destination
            MouvementRubriqueMata::create([
                'compte_id' => $compte->id,
                'rubrique' => $rubriqueDestination,
                'montant' => $montant,
                'solde_rubrique' => $nouveauSoldeDestination,
                'solde_global' => $compte->solde,
                'type_mouvement' => 'versement',
                'reference_operation' => $reference,
                'description' => "Transfert depuis {$rubriqueSource}"
            ]);
            
            DB::commit();
            
            return [
                'rubrique_source' => $rubriqueSource,
                'rubrique_destination' => $rubriqueDestination,
                'montant' => $montant,
                'nouveau_solde_source' => $nouveauSoldeSource,
                'nouveau_solde_destination' => $nouveauSoldeDestination,
                'solde_global' => $compte->solde
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur transfert rubriques MATA: ' . $e->getMessage());
            throw $e;
        }
    }
}