<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Frais\CalculFraisService;
use App\Models\compte\Compte;
use App\Models\compte\TypeCompte;
use App\Models\frais\FraisCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalculFraisServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_appliquer_frais_ouverture_mata()
    {
        // Créer un type de compte MATA
        $typeCompte = TypeCompte::create([
            'code' => '23',
            'libelle' => 'MATA BOOST JOURNALIER',
            'est_mata' => true
        ]);
        
        // Créer la configuration des frais
        $fraisCommission = FraisCommission::create([
            'type_compte_id' => $typeCompte->id,
            'frais_ouverture' => 500,
            'frais_ouverture_actif' => true,
            'compte_commission_paiement' => '72100000'
        ]);
        
        // Créer un compte
        $compte = Compte::create([
            'numero_compte' => 'TEST001',
            'client_id' => 1,
            'type_compte_id' => $typeCompte->id,
            'plan_comptable_id' => 1,
            'devise' => 'FCFA',
            'solde' => 2000,
            'rubriques_mata' => json_encode(['SANTÉ', 'BUSINESS'])
        ]);
        
        $service = new CalculFraisService();
        $result = $service->appliquerFraisOuverture($compte);
        
        $this->assertNotNull($result);
        $this->assertEquals(1500, $compte->fresh()->solde);
        $this->assertEquals('ouverture', $result->type_frais);
    }
    
    public function test_calculer_commission_mensuelle_avec_seuil()
    {
        $typeCompte = TypeCompte::create([
            'code' => '23',
            'libelle' => 'MATA BOOST JOURNALIER',
            'est_mata' => true
        ]);
        
        $fraisCommission = FraisCommission::create([
            'type_compte_id' => $typeCompte->id,
            'commission_mouvement_actif' => true,
            'seuil_commission_mensuelle' => 50000,
            'commission_mensuelle_elevee' => 1000,
            'commission_mensuelle_basse' => 300
        ]);
        
        $service = new CalculFraisService();
        
        // Test avec seuil atteint
        $commission = $fraisCommission->calculerCommissionMensuelle(60000);
        $this->assertEquals(1000, $commission);
        
        // Test avec seuil non atteint
        $commission = $fraisCommission->calculerCommissionMensuelle(40000);
        $this->assertEquals(300, $commission);
    }
}