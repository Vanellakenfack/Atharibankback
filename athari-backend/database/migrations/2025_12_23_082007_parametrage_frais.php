<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametrage_frais', function (Blueprint $table) {
            $table->id();
            
            // Références
            $table->foreignId('type_compte_id')->nullable()->constrained('types_comptes')->onDelete('set null');
            $table->foreignId('plan_comptable_id')->nullable()->constrained('plan_comptable')->onDelete('set null');
            
            // Identification
            $table->string('code_frais', 50)->unique()->comment('Code unique du frais');
            $table->string('libelle_frais');
            $table->text('description')->nullable();
            
            // Type de frais
            $table->enum('type_frais', [
                'OUVERTURE',
                'TENUE_COMPTE',
                'SMS',
                'RETRAIT',
                'COLLECTE',
                'DEBLOCAGE',
                'PENALITE',
                'INTERET',
                'AUTRE'
            ]);
            
            // Base de calcul
            $table->enum('base_calcul', [
                'FIXE',
                'POURCENTAGE_SOLDE',
                'POURCENTAGE_VERSEMENT',
                'POURCENTAGE_RETRAIT',
                'SEUIL_COLLECTE',
                'NON_APPLICABLE'
            ])->default('FIXE');
            
            // Montants et taux
            $table->decimal('montant_fixe', 15, 2)->nullable();
            $table->decimal('taux_pourcentage', 5, 2)->nullable()->comment('Pourcentage si applicable');
            $table->decimal('seuil_minimum', 15, 2)->nullable()->comment('Seuil pour calcul variable');
            $table->decimal('montant_seuil_atteint', 15, 2)->nullable();
            $table->decimal('montant_seuil_non_atteint', 15, 2)->nullable();
            
            // Périodicité
            $table->enum('periodicite', [
                'PONCTUEL',
                'QUOTIDIEN',
                'HEBDOMADAIRE',
                'MENSUEL',
                'TRIMESTRIEL',
                'ANNUEL',
                'PAR_OPERATION'
            ])->default('PONCTUEL');
            
            $table->integer('jour_prelevement')->nullable()->comment('Jour du mois pour prélèvement');
            $table->time('heure_prelevement')->nullable()->comment('Heure de prélèvement');
            
            // Conditions spécifiques
            $table->boolean('prelevement_si_debiteur')->default(false)->comment('Prélever même si compte débiteur');
            $table->boolean('bloquer_operation')->default(false)->comment('Bloquer opération si solde insuffisant');
            $table->decimal('solde_minimum_operation', 15, 2)->nullable()->comment('Solde minimum pour autoriser opération');
            $table->boolean('necessite_autorisation')->default(false)->comment('Nécessite autorisation spéciale');
            
            // Comptes comptables
            $table->foreignId('compte_produit_id')->constrained('plan_comptable')->comment('Compte produit à créditer');
            $table->foreignId('compte_attente_id')->nullable()->constrained('plan_comptable')->comment('Compte d\'attente si solde insuffisant');
            
            // Paramètres techniques
            $table->json('regles_speciales')->nullable()->comment('Règles spéciales en JSON');
            $table->enum('etat', ['ACTIF', 'INACTIF', 'ARCHIVE'])->default('ACTIF');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('type_compte_id');
            $table->index('type_frais');
            $table->index('periodicite');
            $table->index('etat');
        });
    }

    public function down(): void
    {
Schema::disableForeignKeyConstraints();
        
        Schema::dropIfExists('parametrage_frais');
        
        // On les réactive après
        Schema::enableForeignKeyConstraints();    }
};