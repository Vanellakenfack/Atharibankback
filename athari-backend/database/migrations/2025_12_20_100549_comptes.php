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
            $table->string('numero_compte', 13)->unique()->comment('Numéro unique du compte (13 caractères)');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')->comment('Référence au client propriétaire');
            $table->foreignId('type_compte_id')->constrained('types_comptes')->comment('Type de compte (épargne, courant, etc.)');
            $table->foreignId('plan_comptable_id')->constrained('plan_comptable')->comment('Chapitre comptable associé');
            
            // Devise
            $table->enum('devise', ['FCFA', 'EURO', 'DOLLAR', 'POUND'])->default('FCFA')->comment('Devise du compte');
            
            // Informations gestionnaire
            $table->string('code_gestionnaire', 20)->comment('Code identifiant du gestionnaire');
            
            // Spécificités MATA (6 rubriques)
            $table->json('rubriques_mata')->nullable()->comment('Pour comptes MATA: SANTÉ, BUSINESS, FETE, FOURNITURE, IMMO, SCOLARITÉ');
            
            // Durée de blocage (DAT/Bloqué)
            $table->integer('duree_blocage_mois')->nullable()->comment('Durée en mois pour comptes DAT/Bloqués (3-12 mois)');
            
            // Statut et solde
            $table->enum('statut', ['actif', 'inactif', 'cloture', 'suspendu'])->default('actif')->comment('Statut du compte');
            $table->decimal('solde', 15, 2)->default(0)->comment('Solde actuel du compte');
            
            // Documents et validation
            $table->boolean('notice_acceptee')->default(false)->comment('Acceptation de la notice d\'engagement');
            $table->timestamp('date_acceptation_notice')->nullable()->comment('Date d\'acceptation de la notice');
            $table->string('signature_path')->nullable()->comment('Chemin vers la signature numérique');
            
            // Métadonnées
            $table->timestamp('date_ouverture')->useCurrent()->comment('Date d\'ouverture du compte');
            $table->timestamp('date_cloture')->nullable()->comment('Date de clôture du compte');
            $table->text('observations')->nullable()->comment('Observations diverses');
    
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