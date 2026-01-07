<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. IMPORTANT : Nettoyage du cache des permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. DÉFINITION DES PERMISSIONS
        $permissions = [
            // ACL & Utilisateurs
            'gerer utilisateurs',
            'gerer roles et permissions',
            'consulter logs',
            
            // Comptes
            'ouvrir compte',
            'ouvrir collecteur',
            'ouvrir liason',
            'cloturer compte',
            'supprimer compte',
            'gestion agence',
            
            // Caisse & Trésorerie
            'saisir depot retrait',
            'valider operation caisse',
            'saisir od', 
            'edition du journal des od',
            'edition du journal de caisse',
            'parametage plan comptable',
            'valider les od',

            // Crédit (Plafonds)
            'valider credit:500k',
            'valider credit:2m',
            'valider credit:gros',
            
            // Reporting
            'generer etats financiers',

            // Opérations Agence
            'ouverture/fermeture caisse',
            'ouverture/fermeture guichet',
            'ouverture/fermeture agence',

            // Clients & DAT
            'gestion des clients',
            'gestion des gestionnaires',
            'saisir dat',
            'valider dat',
            'cloturer dat',
        ];

        // Création des permissions physiques dans la table 'permissions'
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 3. CRÉATION DES RÔLES ET LIAISONS (Remplit 'role_has_permissions')

        // DG : Accès total
        $roleDG = Role::firstOrCreate(['name' => 'DG', 'guard_name' => 'web']);
        $roleDG->syncPermissions(Permission::all());

        // Admin : Accès total
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(Permission::all());

        // Chef Comptable (CC)
        $roleCC = Role::firstOrCreate(['name' => 'Chef Comptable', 'guard_name' => 'web']);
        $roleCC->syncPermissions([
            'generer etats financiers', 
            'saisir od', 
            'valider credit:2m', 
            'valider credit:500k', 
            'gerer utilisateurs', 
            'cloturer compte',
            'saisir dat', 'valider dat', 'cloturer dat',
        ]);

        // Chef d'Agence (CA)
        $roleCA = Role::firstOrCreate(['name' => "Chef d'Agence (CA)", 'guard_name' => 'web']);
        $roleCA->syncPermissions([
            'ouvrir compte', 
            'valider operation caisse', 
            'valider credit:500k',
            'ouverture/fermeture guichet', 
            'ouverture/fermeture agence',
            'valider les od', 
            'valider dat', 
            'cloturer dat',
        ]);

        // Assistant Juridique (AJ)
        $roleAJ = Role::firstOrCreate(['name' => 'Assistant Juridique (AJ)', 'guard_name' => 'web']);
        $roleAJ->syncPermissions([
            'valider credit:2m', 
            'valider credit:500k', 
            'ouvrir compte', 
            'consulter logs'
        ]);

        // Assistant Comptable (AC)
        $roleAC = Role::firstOrCreate(['name' => 'Assistant Comptable (AC)', 'guard_name' => 'web']);
        $roleAC->syncPermissions(['saisir od', 'generer etats financiers', 'saisir dat']);

        // Autres rôles
        Role::firstOrCreate(['name' => 'Caissière', 'guard_name' => 'web'])->syncPermissions(['saisir depot retrait']);
        Role::firstOrCreate(['name' => 'Agent de Crédit (AC)', 'guard_name' => 'web'])->syncPermissions(['consulter logs']);
        Role::firstOrCreate(['name' => 'Collecteur', 'guard_name' => 'web'])->syncPermissions(['saisir depot retrait']);
        Role::firstOrCreate(['name' => 'Audit/Contrôle (IV)', 'guard_name' => 'web'])->syncPermissions(['consulter logs']);

        // 4. CRÉATION DES UTILISATEURS ET ASSIGNATION (Remplit 'model_has_roles')

        // Compte DG
        $userDG = User::firstOrCreate(
            ['email' => 'dg@example.com'],
            [
                'name' => 'Directeur Général',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $userDG->assignRole($roleDG);

        // Compte Admin
        $userAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrateur Système',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $userAdmin->assignRole($roleAdmin);

        $this->command->info('Tables des permissions, rôles et utilisateurs synchronisées !');
    }
}