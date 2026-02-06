<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();

            // Identifiants
            $table->string('numero_demande')->unique();

            // Relations
            $table->foreignId('compte_id')
                  ->constrained('comptes')
                  ->cascadeOnDelete();

            $table->foreignId('credit_type_id')
                  ->constrained('credit_types')
                  ->cascadeOnDelete();

            // Données du crédit
            $table->decimal('montant', 15, 2);
            $table->integer('duree'); // en mois
            $table->decimal('taux_interet', 5, 2);
            $table->decimal('frais_dossier', 15, 2)->nullable();

            // Infos financières client
            $table->string('source_revenus');
            $table->decimal('revenus_mensuels', 15, 2);
            $table->decimal('autres_revenus', 15, 2)->nullable();

            // Dettes
            $table->decimal('montant_dettes', 15, 2)->nullable();
            $table->text('description_dette')->nullable();

            // Infos bancaires
            $table->string('nom_banque')->nullable();
            $table->string('numero_banque')->nullable();

            // Analyse crédit
            $table->enum('statut', [
                'SOUMIS',
                'EN_ANALYSE',
                'APPROUVE',
                'REJETE',
                'MIS_EN_PLACE'
            ])->default('SOUMIS');

            $table->string('code_mise_en_place')->nullable();
            $table->integer('note_credit')->nullable();
            $table->boolean('plan_epargne')->default(false);

            // Documents (chemins fichiers)
            $table->string('photo_4x4')->nullable();
            $table->string('plan_localisation')->nullable();
            $table->string('facture_electricite')->nullable();
            $table->string('casier_judiciaire')->nullable();
            $table->string('historique_compte')->nullable();

            // Autres
            $table->date('date_demande');
            $table->text('observation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};
