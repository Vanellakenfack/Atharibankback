<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table des comptes bancaires
 * Gère les informations principales des comptes clients
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            
            // Informations du compte
            $table->string('numero_compte', 13)->unique();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')->comment('Référence au client propriétaire');
            $table->foreignId('type_compte_id')->constrained('types_comptes')->onDelete('restrict')->comment('Type de compte (épargne, courant, etc.)');
            $table->foreignId('plan_comptable_id')->constrained('plan_comptable')->onDelete('restrict')->comment('Chapitre comptable associé');
            
            // Devise
            $table->enum('devise', ['FCFA', 'EURO', 'DOLLAR', 'POUND'])->default('FCFA')->comment('Devise du compte');
            
            $table->foreignId('gestionnaire_id')->nullable()->constrained('gestionnaires')->nullOnDelete()
                ->comment('Gestionnaire de compte assigné');

         // 2. LE PORTEUR / CRÉATEUR (L'utilisateur connecté qui a saisi le compte)
       $table->foreignId('created_by')->constrained('users')
          ->comment('ID de l\'utilisateur qui a créé le compte en système');

            // Spécificités MATA (6 rubriques)
            $table->json('rubriques_mata')->nullable()->comment('Pour comptes MATA: SANTÉ, BUSINESS, FETE, FOURNITURE, IMMO, SCOLARITÉ');
            
            // Durée de blocage (DAT/Bloqué)
            $table->integer('duree_blocage_mois')->nullable()->comment('Durée en mois pour comptes DAT/Bloqués (3-12 mois)');
            
            // Statut et solde
         $table->enum('statut', ['en_attente', 'actif', 'cloture', 'bloque'])->default('en_attente');  
                   $table->decimal('solde', 15, 2)->default(0)->comment('Solde actuel du compte');
            
            // Documents et validation
            $table->boolean('notice_acceptee')->default(false)->comment('Acceptation de la notice d\'engagement');
            $table->timestamp('date_acceptation_notice')->nullable()->comment('Date d\'acceptation de la notice');
            $table->string('signature_path')->nullable()->comment('Chemin vers la signature numérique');
            
            // Métadonnées
            $table->timestamp('date_ouverture')->useCurrent()->comment('Date d\'ouverture du compte');
            $table->timestamp('date_cloture')->nullable()->comment('Date de clôture du compte');
            $table->text('observations')->nullable()->comment('Observations diverses');

                        $table->timestamp('date_echeance')->nullable()
                    ->comment('Date précise de fin de blocage calculée à l\'ouverture');

            // === GESTION DE L'OPPOSITION (Saisie, Perte carnet, Litige) ===
            $table->boolean('est_en_opposition')->default(true)
                ->comment('Bloque tout retrait si vrai');
            $table->string('motif_opposition')->nullable();
            $table->timestamp('date_opposition')->nullable();
            

            // Workflow de double signature
            $table->boolean('validation_chef_agence')->default(false);
            $table->boolean('validation_juridique')->default(false);
            $table->timestamp('date_validation_juridique')->nullable();
          $table->timestamp('date_dernier_prelevement_frais')->nullable();

           $table->foreignId('ca_id')->nullable()->constrained('users')->onDelete('set null');

           $table->foreignId('rejete_par')->nullable()->constrained('users')->onDelete('set null');

            // --- Logique de validation juridique ---
            $table->json('checklist_juridique')->nullable()->comment('Stocke les checkbox de conformité');
            $table->boolean('dossier_complet')->default(false);

            // --- Logique de rejet ---
            $table->text('motif_rejet')->nullable();
            $table->timestamp('date_rejet')->nullable();
            

    
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimisation
            $table->index('client_id');
            $table->index('type_compte_id');
            $table->index('plan_comptable_id');
            $table->index('statut');
            $table->index('devise');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};