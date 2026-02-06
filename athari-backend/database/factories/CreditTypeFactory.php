<?php

namespace Database\Factories\Credit;

use App\Models\Credit\CreditType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit\CreditType>
 */
class CreditTypeFactory extends Factory
{
    protected $model = CreditType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->word(),
            'description' => fake()->sentence(),
            'montant_min' => fake()->numberBetween(50000, 200000),
            'montant_max' => fake()->numberBetween(500000, 5000000),
            'duree_min' => fake()->numberBetween(3, 12),
            'duree_max' => fake()->numberBetween(24, 120),
            'taux_interet_min' => fake()->randomFloat(2, 2, 5),
            'taux_interet_max' => fake()->randomFloat(2, 5, 15),
            'frais_dossier' => fake()->numberBetween(5000, 50000),
            'frais_etude' => fake()->numberBetween(2000, 20000),
            'penalite_par_jour' => fake()->numberBetween(50, 500),
            'statut' => 'ACTIF',
            'details_supplementaires' => json_encode([
                'garanties_requises' => fake()->boolean(),
                'documents_obligatoires' => ['CIN', 'Bulletin de salaire'],
            ]),
        ];
    }
}
