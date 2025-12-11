<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // IMPORTANT: Réinitialise le cache des permissions pour que les nouvelles permissions soient visibles immédiatement
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. DÉFINITION DES PERMISSIONS (ACTIONS) ---

        $permissions = [
            // ACL & Utilisateurs
            'gerer utilisateurs',
            'gerer roles et permissions',
            'consulter logs', // Utilisé par Audit, AJ, AC
            
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
            'saisir od', // Opération Diverse
            'edition du journal des od',
           'edition du journal de caisse',
           'parametage plan comptable',
           'valider les od',
           

            // Crédit (Permissions basées sur les PLAFONDS)
            'valider credit:500k',   // Habilitation max: Chef d'Agence (CA)
            'valider credit:2m',     // Habilitation max: Chef Comptable (CC) / Assistant Juridique (AJ)
            'valider credit:gros',   // Habilitation max: DG
            
            // Reporting
            'generer etats financiers',

            //ouverture caisse
            'ouverture/fermeture caisse',
            'ouverture/fermeture guichet',
            'ouverture/fermeture agence',

        //  des clients
        'gestion des clients',
        //gestionnaires
        'gestion des gestionnaires',





        ];

          foreach ($permissions as $permission) {
              Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
          }

        // --- 2. CRÉATION DES 9 RÔLES ET ATTRIBUTION ---

        // Rôle 1: DG (Accès total)
        $roleDG = Role::firstOrCreate(['name' => 'DG', 'guard_name' => 'web']);
        $roleDG->givePermissionTo(Permission::where('guard_name', 'web')->get());
        
        // Rôle 2: Chef Comptable (CC) - Validation intermédiaire, Comptabilité, Crédits 2M
        $roleCC = Role::firstOrCreate(['name' => 'Chef Comptable', 'guard_name' => 'web']);
        $roleCC->givePermissionTo([
            'generer etats financiers', 
            'saisir od', 
            'valider credit:2m', // Habilitation plafond 2.000.000 XAF
            'valider credit:500k', // Implicite: peut valider en dessous de son plafond
            'gerer utilisateurs',
            'cloturer compte',
        ]);

        // Rôle 3: Chef d'Agence (CA) - Opérations Agence, Crédits 500K
        $roleCA = Role::firstOrCreate(['name' => 'Chef d\'Agence (CA)', 'guard_name' => 'web']);
        $roleCA->givePermissionTo([
            'ouvrir compte', 
            'valider operation caisse',
            'valider credit:500k', // Habilitation plafond 500.000 XAF
            'ouverture/fermeture guichet',
            'ouverture/fermeture agence',
             'valider les od',



        ]);

        // Rôle 4: Assistant Juridique (AJ) - Conformité, Crédits 2M
        $roleAJ = Role::firstOrCreate(['name' => 'Assistant Juridique (AJ)', 'guard_name' => 'web']);
        $roleAJ->givePermissionTo([
            'valider credit:2m', // Co-validation pour le plafond 2M
            'valider credit:500k', // Implicite
            'ouvrir compte',
            'consulter logs',
        ]);

        // Rôle 5: Assistant Comptable (AC) - Saisie
        $roleAC = Role::firstOrCreate(['name' => 'Assistant Comptable (AC)', 'guard_name' => 'web']);
        $roleAC->givePermissionTo(['saisir od', 'generer etats financiers']);

        // Rôle 6: Caissière - Opérations Caisse
        Role::firstOrCreate(['name' => 'Caissière', 'guard_name' => 'web'])->givePermissionTo('saisir depot retrait');

        // Rôle 7: Agent de Crédit (AC) - Collecte et consultation
        Role::firstOrCreate(['name' => 'Agent de Crédit (AC)', 'guard_name' => 'web'])->givePermissionTo('consulter logs');
        
        // Rôle 8: Collecteur - Mobile App (Dépôt/Retrait)
        Role::firstOrCreate(['name' => 'Collecteur', 'guard_name' => 'web'])->givePermissionTo('saisir depot retrait');
        
        // Rôle 9: Audit/Contrôle (IV) - Lecture seule
        Role::firstOrCreate(['name' => 'Audit/Contrôle (IV)', 'guard_name' => 'web'])->givePermissionTo('consulter logs');

        // Rôle 10: Admin (Accès complet)
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])->givePermissionTo(Permission::where('guard_name', 'web')->get());
    }
}