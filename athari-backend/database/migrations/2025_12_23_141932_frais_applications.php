<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_applications', function (Blueprint $table) {
            $table->id();
            
            // Références
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->foreignId('frais_commission_id')->constrained('frais_commissions')->onDelete('cascade');
            
            // Type de frais
            $table->enum('type_frais', [
                'ouverture',
                'tenue_compte',
                'commission_mouvement',
                'commission_retrait',
                'commission_sms',
                'deblocage',
                'cloture_anticipe',
                'penalite',
                'interet',
                'minimum_compte'
            ]);
            
            // Informations sur l'opération
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_avant', 15, 2)->comment('Solde du compte avant application');
            $table->decimal('solde_apres', 15, 2)->comment('Solde du compte après application');
            
            // Pour les frais spécifiques à MATA
            $table->string('rubrique_mata')->nullable()->comment('Rubrique MATA concernée');
            $table->decimal('versement_rubrique', 15, 2)->nullable()->comment('Montant du versement sur la rubrique');
            
            // Pour les intérêts
            $table->date('date_debut_periode')->nullable();
            $table->date('date_fin_periode')->nullable();
            
            // Pour les commissions mensuelles
            $table->decimal('total_versements_mois', 15, 2)->nullable()->comment('Total des versements du mois');
            $table->integer('nombre_retraits_mois')->nullable()->comment('Nombre de retraits du mois');
            
            // Comptes comptables impactés
            $table->string('compte_debit')->nullable()->comment('Compte débité (client)');
            $table->string('compte_credit')->nullable()->comment('Compte crédité (produit/attente)');
            
            // Statut
            $table->enum('statut', ['applique', 'en_attente', 'annule', 'reporte'])->default('applique');
            $table->boolean('est_automatique')->default(true);
            
            // Validation
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->timestamp('valide_le')->nullable();
            
            // Métadonnées
            $table->date('date_application');
            $table->timestamp('date_effet')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('compte_id');
            $table->index('type_frais');
            $table->index('date_application');
            $table->index(['compte_id', 'type_frais', 'statut']);
            $table->index('rubrique_mata');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_applications');
    }
};