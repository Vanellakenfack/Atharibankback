<?php

namespace Tests\Feature;

use App\Models\Credit\AvisCredit;
use App\Models\Credit\CreditApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $creditApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with CA role
        $this->user = User::factory()->create();
        $this->user->assignRole('CA');

        // Create a credit application with proper relationships
        $this->creditApplication = CreditApplication::factory()->create([
            'statut' => 'SOUMIS' // Ensure it's in the correct initial status
        ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_opinion()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'INVALID',
            'commentaire' => 'Test comment',
            'niveau_avis' => 'CA',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Erreur de validation',
                     'errors' => [
                         'opinion' => ['The selected opinion is invalid.']
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_409_for_duplicate_avis()
    {
        $this->actingAs($this->user);

        // Create first avis
        $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'FAVORABLE',
            'commentaire' => 'Test comment',
            'niveau_avis' => 'CA',
        ]);

        // Try to create duplicate avis
        $response = $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'DEFAVORABLE',
            'commentaire' => 'Another comment',
            'niveau_avis' => 'CA',
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Vous avez déjà donné un avis à ce niveau'
                 ]);
    }

    /** @test */
    public function it_allows_any_authenticated_user_to_give_avis()
    {
        // Create user with any role
        $anyUser = User::factory()->create();
        $anyUser->assignRole('AC'); // Even AC can now give avis

        $this->actingAs($anyUser);

        $response = $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'FAVORABLE',
            'commentaire' => 'Test comment',
            'niveau_avis' => 'GENERAL',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Avis soumis avec succès'
                 ]);
    }

    /** @test */
    public function it_creates_avis_successfully()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'FAVORABLE',
            'commentaire' => 'Test comment',
            'score_risque' => 50,
            'niveau_avis' => 'CA',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Avis soumis avec succès'
                 ]);

        $this->assertDatabaseHas('avis_credits', [
            'credit_application_id' => $this->creditApplication->id,
            'user_id' => $this->user->id,
            'opinion' => 'FAVORABLE',
            'niveau_avis' => 'CA',
        ]);
    }

    /** @test */
    public function it_creates_avis_with_default_values()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/credit-applications/{$this->creditApplication->id}/avis", [
            'opinion' => 'FAVORABLE',
            'commentaire' => 'Test comment',
            // niveau_avis not provided, should default to GENERAL
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Avis soumis avec succès'
                 ]);

        $this->assertDatabaseHas('avis_credits', [
            'credit_application_id' => $this->creditApplication->id,
            'user_id' => $this->user->id,
            'opinion' => 'FAVORABLE',
            'niveau_avis' => 'GENERAL', // Should default to GENERAL
        ]);
    }
}


