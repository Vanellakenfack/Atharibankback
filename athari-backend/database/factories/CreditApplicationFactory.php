<?php

namespace Database\Factories\Credit;

use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditType;
use App\Models\Compte\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit\CreditApplication>
 */
class CreditApplicationFactory extends Factory
{
    protected $model = CreditApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_demande' => 'CRD-' . fake()->unique()->numberBetween(1000, 9999),
            'compte_id' => Compte::factory(),
            'credit_type_id' => CreditType::factory(),
            'montant' => fake()->numberBetween(100000, 10000000),
            'duree' => fake()->numberBetween(6, 60),
            'taux_interet' => fake()->randomFloat(2, 5, 15),
            'interet_total' => fake()->numberBetween(50000, 500000),
            'frais_dossier' => fake()->numberBetween(10000, 50000),
            'frais_etude' => fake()->numberBetween(5000, 25000),
            'montant_total' => fake()->numberBetween(200000, 11000000),
            'penalite_par_jour' => fake()->numberBetween(100, 1000),
            'calcul_details' => json_encode([
                'mensualite' => fake()->numberBetween(20000, 200000),
                'total_interets' => fake()->numberBetween(50000, 500000),
                'total_frais' => fake()->numberBetween(15000, 75000),
            ]),
            'date_demande' => fake()->dateTimeBetween('-1 month', 'now'),
            'observation' => fake()->optional()->sentence(),
            'source_revenus' => fake()->randomElement(['Salaire', 'Commerce', 'Agriculture', 'Autre']),
            'revenus_mensuels' => fake()->numberBetween(50000, 500000),
            'autres_revenus' => fake()->optional()->numberBetween(0, 100000),
            'montant_dettes' => fake()->optional()->numberBetween(0, 200000),
            'description_dette' => fake()->optional()->sentence(),
            'nom_banque' => fake()->optional()->company(),
            'numero_banque' => fake()->optional()->bankAccountNumber(),
            'statut' => 'SOUMIS',
            'code_mise_en_place' => fake()->optional()->uuid(),
            'note_credit' => fake()->optional()->numberBetween(1, 10),
            'plan_epargne' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the credit application is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'APPROUVE',
        ]);
    }

    /**
     * Indicate that the credit application is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'REJETE',
        ]);
    }
}
