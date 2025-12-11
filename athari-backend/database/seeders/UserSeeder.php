<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Assurez-vous d'importer votre modèle User
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role; // Importe le modèle de rôle de Spatie

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Assurez-vous que les rôles existent avant de les assigner
        // Il est recommandé d'exécuter d'abord PermissionsSeeder
        if (Role::count() === 0) {
            $this->call(PermissionsSeeder::class);
        }

        // --- 1. Utilisateur DG (Accès total) ---
        $userDG = User::firstOrCreate(
            ['email' => 'dg@example.com'],
            [
                'name' => 'Directeur Général',
                'password' => Hash::make('password'),
            ]
        );
        $userDG->assignRole('DG');


        // --- 2. Utilisateur Admin (Accès total) ---
        $userAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrateur Système',
                'password' => Hash::make('password'),
            ]
        );
        $userAdmin->assignRole('Admin');


        // --- 3. Utilisateur Chef Comptable ---
        $userCC = User::firstOrCreate(
            ['email' => 'cc@example.com'],
            [
                'name' => 'Chef Comptable',
                'password' => Hash::make('password'),
            ]
        );
        $userCC->assignRole('Chef Comptable');


        // --- 4. Utilisateur Chef d'Agence (CA) ---
        $userCA = User::firstOrCreate(
            ['email' => 'ca@example.com'],
            [
                'name' => 'Chef d\'Agence',
                'password' => Hash::make('password'),
            ]
        );
        $userCA->assignRole('Chef d\'Agence (CA)');


        // --- 5. Utilisateur Assistant Comptable (AC) ---
        $userAC = User::firstOrCreate(
            ['email' => 'ac@example.com'],
            [
                'name' => 'Assistant Comptable',
                'password' => Hash::make('password'),
            ]
        );
        $userAC->assignRole('Assistant Comptable (AC)');


        // --- 6. Utilisateur Collecteur (Rôle Mobile) ---
        $userCollecteur = User::firstOrCreate(
            ['email' => 'collecteur@example.com'],
            [
                'name' => 'Agent Collecteur',
                'password' => Hash::make('password'),
            ]
        );
        $userCollecteur->assignRole('Collecteur');


        // --- 7. Utilisateur Caissière ---
        $userCaissiere = User::firstOrCreate(
            ['email' => 'caisse@example.com'],
            [
                'name' => 'Caissière Principale',
                'password' => Hash::make('password'),
            ]
        );
        $userCaissiere->assignRole('Caissière');
        
        $this->command->info('Utilisateurs de test créés avec succès !');
    }
}