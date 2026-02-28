<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class AssignUsersToAgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'agence 9
        $agency = Agency::find(9);
        
        if (!$agency) {
            $this->command->error('Agence 9 non trouvée!');
            return;
        }

        // Récupérer tous les utilisateurs
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé dans la base de données.');
            return;
        }

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            // Vérifier si l'utilisateur est déjà assigné à l'agence 9
            $exists = DB::table('agency_user')
                ->where('user_id', $user->id)
                ->where('agency_id', 9)
                ->exists();

            if (!$exists) {
                // Assigner l'utilisateur à l'agence 9 avec is_primary = true
                DB::table('agency_user')->insert([
                    'user_id' => $user->id,
                    'agency_id' => 9,
                    'is_primary' => true,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assignedCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->command->info("✅ {$assignedCount} utilisateur(s) assigné(s) à l'agence 9");
        if ($skippedCount > 0) {
            $this->command->warn("⚠️ {$skippedCount} utilisateur(s) déjà assigné(s)");
        }
    }
}
