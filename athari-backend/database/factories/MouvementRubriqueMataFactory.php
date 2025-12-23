<?php
// database/factories/MouvementRubriqueMataFactory.php

namespace Database\Factories;

use App\Models\MouvementRubriqueMata;
use App\Models\compte\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

class MouvementRubriqueMataFactory extends Factory
{
    protected $model = MouvementRubriqueMata::class;
    
    public function definition()
    {
        $rubriques = ['SANTÉ', 'BUSINESS', 'FETE', 'FOURNITURE', 'IMMO', 'SCOLARITÉ'];
        
        return [
            'compte_id' => Compte::factory(),
            'rubrique' => $this->faker->randomElement($rubriques),
            'montant' => $this->faker->numberBetween(1000, 50000),
            'solde_rubrique' => $this->faker->numberBetween(0, 100000),
            'solde_global' => $this->faker->numberBetween(0, 500000),
            'type_mouvement' => $this->faker->randomElement(['versement', 'retrait', 'commission']),
            'reference_operation' => 'REF-' . $this->faker->unique()->numerify('######'),
            'description' => $this->faker->sentence()
        ];
    }
}