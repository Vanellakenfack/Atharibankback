<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditType;
use App\Models\chapitre\PlanComptable;

class CreditTypePlanComptableSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping type de crédit → codes des plans comptables
        $mapping = [
            'Crédit Personnel' => ['101', '701', '702'],
            'Crédit DAT 30 Jours' => ['701', '702'],
            'Crédit DAT 9 Mois' => ['701', '702'],
        ];

        foreach ($mapping as $creditNom => $codes) {

            // Récupérer le type de crédit
            $creditType = CreditType::where('nom', $creditNom)->first();

            if (!$creditType) {
                // Log si le type de crédit n'existe pas
                $this->command->info("Type de crédit non trouvé : {$creditNom}");
                continue;
            }

            // Récupérer les IDs des plans comptables actifs
            $planIds = PlanComptable::actif()
                ->whereIn('code', $codes)
                ->pluck('id')
                ->toArray(); // s'assurer que c'est un tableau

            if (empty($planIds)) {
                $this->command->info("Aucun plan comptable trouvé pour : {$creditNom}");
                continue;
            }

            // Lier les plans comptables au type de crédit
            $creditType->plansComptables()->sync($planIds);

            $this->command->info("Plans comptables liés pour : {$creditNom}");
        }
    }
}
