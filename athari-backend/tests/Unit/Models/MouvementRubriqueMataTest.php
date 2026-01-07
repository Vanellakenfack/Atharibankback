<?php
// tests/Unit/Models/MouvementRubriqueMataTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\frais\MouvementRubriqueMata;
use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MouvementRubriqueMataTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creer_mouvement_rubrique()
    {
        $typeCompte = TypeCompte::create([
            'code' => '23',
            'libelle' => 'MATA BOOST JOURNALIER',
            'est_mata' => true
        ]);
        
        $compte = Compte::create([
            'numero_compte' => 'TEST001',
            'client_id' => 1,
            'type_compte_id' => $typeCompte->id,
            'plan_comptable_id' => 1,
            'devise' => 'FCFA',
            'solde' => 10000,
            'rubriques_mata' => json_encode(['SANTÉ', 'BUSINESS'])
        ]);
        
        $mouvement = MouvementRubriqueMata::create([
            'compte_id' => $compte->id,
            'rubrique' => 'SANTÉ',
            'montant' => 5000,
            'solde_rubrique' => 5000,
            'solde_global' => 10000,
            'type_mouvement' => 'versement',
            'description' => 'Test versement'
        ]);
        
        $this->assertNotNull($mouvement);
        $this->assertEquals('SANTÉ', $mouvement->rubrique);
        $this->assertEquals(5000, $mouvement->montant);
    }
    
    public function test_get_solde_rubrique()
    {
        $typeCompte = TypeCompte::create([
            'code' => '23',
            'libelle' => 'MATA BOOST JOURNALIER',
            'est_mata' => true
        ]);
        
        $compte = Compte::create([
            'numero_compte' => 'TEST002',
            'client_id' => 1,
            'type_compte_id' => $typeCompte->id,
            'plan_comptable_id' => 1,
            'devise' => 'FCFA',
            'solde' => 20000,
            'rubriques_mata' => json_encode(['SANTÉ'])
        ]);
        
        // Créer plusieurs mouvements
        MouvementRubriqueMata::create([
            'compte_id' => $compte->id,
            'rubrique' => 'SANTÉ',
            'montant' => 5000,
            'solde_rubrique' => 5000,
            'solde_global' => 20000,
            'type_mouvement' => 'versement',
            'description' => 'Versement 1'
        ]);
        
        MouvementRubriqueMata::create([
            'compte_id' => $compte->id,
            'rubrique' => 'SANTÉ',
            'montant' => 3000,
            'solde_rubrique' => 8000,
            'solde_global' => 20000,
            'type_mouvement' => 'versement',
            'description' => 'Versement 2'
        ]);
        
        $solde = MouvementRubriqueMata::getSoldeRubrique($compte->id, 'SANTÉ');
        
        $this->assertEquals(8000, $solde);
    }
}