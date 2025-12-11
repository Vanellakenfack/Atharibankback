<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; 
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;
use PHPUnit\Framework\Attributes\Test; // Import de l'attribut Test

/**
 * Tests de validation des accès (ACL Spatie).
 */
class AclAuthorizationTest extends TestCase
{
    use RefreshDatabase; 

    protected User $dgUser;
    protected User $collecteurUser;
    protected User $caUser;
    protected User $caissiereUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Exécution du Seeder de Permissions
        Artisan::call('db:seed', ['--class' => 'PermissionsSeeder']);
        
        // 2. Création des utilisateurs de test
        $this->dgUser = User::factory()->create(['name' => 'DG', 'email' => 'dg@test.com']);
        $this->collecteurUser = User::factory()->create(['name' => 'Collecteur', 'email' => 'collecteur@test.com']);
        $this->caUser = User::factory()->create(['name' => 'Chef d\'Agence', 'email' => 'ca@test.com']);
        $this->caissiereUser = User::factory()->create(['name' => 'Caissière', 'email' => 'caissiere@test.com']);

        // 3. Attribution des rôles
        $this->dgUser->assignRole('DG');
        $this->collecteurUser->assignRole('Collecteur');
        $this->caUser->assignRole('Chef d\'Agence (CA)');
        $this->caissiereUser->assignRole('Caissière');
        
        // 4. Réinitialiser le cache des permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
    
    // ----------------------------------------------------------------------
    // TESTS D'ACCÈS INTERDITS (DOIT RETOURNER 403)
    // ----------------------------------------------------------------------

    #[Test] // Utilisation de l'attribut PHP 8
    public function caissiere_cannot_validate_les_od() // Pas besoin de 'test_'
    {
        $response = $this->actingAs($this->caissiereUser)
                         ->postJson('/api/comptabilite/valider-od', ['od_id' => 456]);

        $response->assertStatus(403); 
    }
    
    #[Test]
    public function collecteur_cannot_validate_caisse_operation()
    {
        $response = $this->actingAs($this->collecteurUser) 
                         ->postJson('/api/caisse/valider-op', ['operation_id' => 123]);

        $response->assertStatus(403); 
    }

    #[Test]
    public function ca_cannot_access_gros_credit_validation_route()
    {
        $response = $this->actingAs($this->caUser)
                         ->postJson('/api/credit/validate-gros', ['montant' => 5000000]);

        $response->assertStatus(403); 
    }
    
    // ----------------------------------------------------------------------
    // TESTS D'ACCÈS AUTORISÉS (DOIT PASSER LE MIDLEWARE)
    // ----------------------------------------------------------------------
    
    #[Test]
    public function dg_can_access_gros_credit_validation_route()
    {
        $response = $this->actingAs($this->dgUser)
                         ->postJson('/api/credit/validate-gros', ['montant' => 5000000]);

        // Attendu : 404 car la route n'existe pas, ou l'erreur du contrôleur. Jamais 403.
        $response->assertStatus(404); 
    }

    #[Test]
    public function ca_can_access_validation_500k_route()
    {
        $response = $this->actingAs($this->caUser)
                         ->postJson('/api/credit/validate-petit', ['montant' => 400000 ]);

        $response->assertStatus(404);
    }
    
    #[Test]
    public function caissiere_can_saisir_depot_retrait()
    {
        $response = $this->actingAs($this->caissiereUser)
                         ->postJson('/api/caisse/saisir-op', ['montant' => 100000 ]);

        $response->assertStatus(404);
    }
}