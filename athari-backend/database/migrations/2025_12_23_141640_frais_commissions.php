<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_commissions', function (Blueprint $table) {
            $table->id();
            
            // Liaison avec le type de compte
            $table->foreignId('type_compte_id')->constrained('types_comptes')->onDelete('cascade');
            
            // Frais d'ouverture
            $table->decimal('frais_ouverture', 15, 2)->nullable()->comment('Frais à l\'ouverture du compte');
            $table->boolean('frais_ouverture_actif')->default(false);
            
            // Frais mensuels
            $table->decimal('frais_tenue_compte', 15, 2)->nullable()->comment('Frais de tenue de compte mensuels');
            $table->boolean('frais_tenue_actif')->default(false);
            
            // Commission sur mouvement
            $table->decimal('commission_mouvement', 15, 2)->nullable()->comment('Commission sur mouvement de compte');
            $table->enum('commission_mouvement_type', ['fixe', 'pourcentage'])->default('fixe');
            $table->boolean('commission_mouvement_actif')->default(false);
            
            // Commission de retrait
            $table->decimal('commission_retrait', 15, 2)->nullable()->comment('Commission sur retrait');
            $table->boolean('commission_retrait_actif')->default(false);
            
            // Commission SMS
            $table->decimal('commission_sms', 15, 2)->nullable()->comment('Commission SMS mensuelle');
            $table->boolean('commission_sms_actif')->default(false);
            
            // Frais de déblocage (comptes bloqués)
            $table->decimal('frais_deblocage', 15, 2)->nullable()->comment('Frais de déblocage compte bloqué');
            $table->boolean('frais_deblocage_actif')->default(false);
            
            // Frais de clôture anticipée
            $table->decimal('frais_cloture_anticipe', 15, 2)->nullable();
            $table->boolean('frais_cloture_anticipe_actif')->default(false);
            
            // Intérêts créditeurs
            $table->decimal('taux_interet_annuel', 8, 5)->nullable()->comment('Taux d\'intérêt annuel');
            $table->enum('frequence_calcul_interet', ['journalier', 'mensuel', 'annuel'])->default('journalier');
            $table->time('heure_calcul_interet')->default('12:00:00');
            $table->boolean('interets_actifs')->default(false);
            
            // Pénalités de retrait anticipé
            $table->decimal('penalite_retrait_anticipe', 5, 2)->nullable()->comment('Pourcentage de pénalité');
            $table->boolean('penalite_actif')->default(false);
            
            // Minimum en compte
            $table->decimal('minimum_compte', 15, 2)->nullable();
            $table->boolean('minimum_compte_actif')->default(false);
            
            // Conditions spéciales pour MATA
            $table->decimal('seuil_commission_mensuelle', 15, 2)->nullable()->comment('Seuil pour commission mensuelle (ex: 50000)');
            $table->decimal('commission_mensuelle_elevee', 15, 2)->nullable()->comment('Commission si seuil atteint');
            $table->decimal('commission_mensuelle_basse', 15, 2)->nullable()->comment('Commission si seuil non atteint');
            
            // Comptes associés
            $table->string('compte_commission_paiement', 20)->nullable()->comment('Compte pour commissions instrument paiement (72100000)');
            $table->string('compte_produit_commission', 20)->nullable()->comment('Compte pour commissions (720510000)');
            $table->string('compte_attente_produits', 20)->nullable()->comment('Compte d\'attente produits (47120)');
            $table->string('compte_attente_sms', 20)->nullable()->comment('Compte d\'attente SMS');
            
            // Validation
            $table->boolean('retrait_anticipe_autorise')->default(false);
            $table->boolean('validation_retrait_anticipe')->default(false)->comment('Nécessite validation pour retrait anticipé');
            
            // Configuration des périodes
            $table->integer('duree_blocage_min')->nullable()->comment('Durée minimale de blocage en mois');
            $table->integer('duree_blocage_max')->nullable()->comment('Durée maximale de blocage en mois');
            
            $table->text('observations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('type_compte_id');
            $table->index(['frais_tenue_actif', 'interets_actifs']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_commissions');
    }
};