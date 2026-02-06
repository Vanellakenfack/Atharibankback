<?php

namespace Database\Factories\Compte;

use App\Models\Compte\Compte;
use App\Models\Client;
use App\Models\TypesComptes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte\Compte>
 */
class CompteFactory extends Factory
{
    protected $model = Compte::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_compte' => fake()->unique()->numerify('##########'),
            'client_id' => Client::factory(),
            'type_compte_id' => TypesComptes::factory(),
            'solde' => fake()->numberBetween(0, 1000000),
            'date_ouverture' => fake()->dateTimeBetween('-2 years', 'now'),
            'statut' => 'ACTIF',
            'code_agence' => fake()->numberBetween(1, 99),
            'rib' => fake()->numerify('####################'),
            'iban' => fake()->iban('FR'),
        ];
    }
}
