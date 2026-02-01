<?php

namespace App\Services;

use App\Models\CreditProduct;

class CreditService
{
    /**
     * Calcule les intérêts pour un crédit flash selon la nouvelle logique
     */
    public function calculateFlashInterests(float $montant, int $duree = 14): array
    {
        $product = CreditProduct::where('code', 'FLASH_24H')->first();
        
        if (!$product) {
            throw new \Exception('Produit FLASH_24H non trouvé');
        }
        
        return $product->calculateFlashDetails($montant, $duree);
    }
    
    /**
     * Calcule les frais d'étude pour un montant donné
     */
    public function calculateFraisEtude(float $montant): float
    {
        $product = CreditProduct::where('code', 'FLASH_24H')->first();
        
        if (!$product) {
            throw new \Exception('Produit FLASH_24H non trouvé');
        }
        
        return $product->calculFraisEtude($montant);
    }
    
    /**
     * Calcule les pénalités de retard
     */
    public function calculatePenalite(float $montant, int $joursRetard = 1): float
    {
        $product = CreditProduct::where('code', 'FLASH_24H')->first();
        
        if (!$product) {
            throw new \Exception('Produit FLASH_24H non trouvé');
        }
        
        return $product->calculPenaliteRetard($montant, $joursRetard);
    }
    
    /**
     * Génère un tableau d'amortissement pour le crédit flash
     */
    public function generateFlashAmortissement(float $montant, int $duree = 14): array
    {
        $product = CreditProduct::where('code', 'FLASH_24H')->first();
        
        if (!$product) {
            throw new \Exception('Produit FLASH_24H non trouvé');
        }
        
        return $product->generateAmortissement($montant, $duree);
    }
    
    /**
     * Simulation complète d'un crédit flash
     */
    public function simulateFlashCredit(float $montant, int $duree = 14): array
    {
        $product = CreditProduct::where('code', 'FLASH_24H')->first();
        
        if (!$product) {
            throw new \Exception('Produit FLASH_24H non trouvé');
        }
        
        $details = $product->calculateFlashDetails($montant, $duree);
        $fraisEtude = $product->calculFraisEtude($montant);
        $penalite = $product->calculPenaliteRetard($montant);
        
        return [
            'simulation' => $details,
            'frais_etude' => $fraisEtude,
            'penalite_par_jour' => $penalite,
            'total_frais' => $details['total_interets'] + $fraisEtude,
            'total_avec_frais' => $montant + $details['total_interets'] + $fraisEtude,
            'produit' => [
                'nom' => $product->nom,
                'code' => $product->code,
                'duree_max' => $product->duree_max,
                'temps_obtention' => $product->temps_obtention
            ]
        ];
    }
}