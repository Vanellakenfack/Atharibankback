<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_comptes', function (Blueprint $table) {
            // === IDENTIFICATION ===
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            
            // === CARACTÉRISTIQUES ===
            $table->boolean('a_vue')->default(false);
            $table->boolean('est_mata')->default(false);
            $table->boolean('necessite_duree')->default(false);
            $table->boolean('actif')->default(true);
            
            // === CHAPITRES COMPTABLES PRINCIPAUX ===
            $table->foreignId('chapitre_defaut_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            // === FRAIS D'OUVERTURE ===
            $table->decimal('frais_ouverture', 15, 2)->default(0);
            $table->boolean('frais_ouverture_actif')->default(false);
            $table->foreignId('chapitre_frais_ouverture_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === FRAIS DE CARNET/LIVRET/CHÉQUIER ===
            $table->decimal('frais_carnet', 15, 2)->default(0);
            $table->boolean('frais_carnet_actif')->default(false);
            $table->foreignId('chapitre_frais_carnet_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            $table->decimal('frais_renouvellement_carnet', 15, 2)->default(0);
            $table->boolean('frais_renouvellement_actif')->default(false);
            $table->foreignId('chapitre_renouvellement_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            $table->decimal('frais_perte_carnet', 15, 2)->default(0);
            $table->boolean('frais_perte_actif')->default(false);
            $table->foreignId('chapitre_perte_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === COMMISSION MENSUELLE ===
            $table->boolean('commission_mensuelle_actif')->default(false);
            $table->decimal('seuil_commission', 15, 2)->nullable()
                ->comment('Seuil des versements pour commission élevée');
            $table->decimal('commission_si_superieur', 15, 2)->nullable()
                ->comment('Commission si versements >= seuil');
            $table->decimal('commission_si_inferieur', 15, 2)->nullable()
                ->comment('Commission si versements < seuil');
            
            // === COMMISSION RETRAIT ===
            $table->decimal('commission_retrait', 15, 2)->default(0);
            $table->boolean('commission_retrait_actif')->default(false);
            $table->foreignId('chapitre_commission_retrait_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === COMMISSION SMS ===
            $table->decimal('commission_sms', 15, 2)->default(0);
            $table->boolean('commission_sms_actif')->default(false);
            $table->foreignId('chapitre_commission_sms_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === INTÉRÊTS CRÉDITEURS ===
            $table->decimal('taux_interet_annuel', 5, 2)->default(0)
                ->comment('Taux en %');
            $table->boolean('interets_actifs')->default(false);
            $table->enum('frequence_calcul_interet', [
                'JOURNALIER', 'MENSUEL', 'ANNUEL'
            ])->default('JOURNALIER');
            $table->time('heure_calcul_interet')->default('12:00:00');
            $table->foreignId('chapitre_interet_credit_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            $table->boolean('capitalisation_interets')->default(false)
                ->comment('Pour DAT > 12 mois');
            
            // === FRAIS DE BLOCAGE/DÉBLOCAGE ===
            $table->decimal('frais_deblocage', 15, 2)->default(0);
            $table->boolean('frais_deblocage_actif')->default(false);
            $table->foreignId('chapitre_frais_deblocage_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === PÉNALITÉS ===
            $table->decimal('penalite_retrait_anticipe', 5, 2)->default(0)
                ->comment('En %');
            $table->boolean('penalite_actif')->default(false);
            $table->foreignId('chapitre_penalite_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            $table->decimal('frais_cloture_anticipe', 15, 2)->default(0);
            $table->boolean('frais_cloture_actif')->default(false);
            $table->foreignId('chapitre_cloture_anticipe_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === MINIMUM EN COMPTE ===
            $table->decimal('minimum_compte', 15, 2)->default(0);
            $table->boolean('minimum_compte_actif')->default(false);
            
            // === COMPTE D'ATTENTE ===
            $table->foreignId('compte_attente_produits_id')->nullable()
                ->constrained('plan_comptable')->nullOnDelete();
            
            // === GESTION RETRAITS ANTICIPÉS ===
            $table->boolean('retrait_anticipe_autorise')->default(false);
            $table->boolean('validation_retrait_anticipe')->default(false);
            
            // === DURÉES DE BLOCAGE ===
            $table->integer('duree_blocage_min')->nullable()
                ->comment('En mois');
            $table->integer('duree_blocage_max')->nullable()
                ->comment('En mois');
            
            // === MÉTADONNÉES ===
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEX ===
            $table->index('code');
            $table->index('actif');
            $table->index(['est_mata', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_comptes');
    }
};