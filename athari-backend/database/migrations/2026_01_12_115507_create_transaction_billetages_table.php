<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_billetages', function (Blueprint $table) {
            $table->id();
            
            // Liaison avec la transaction de caisse
            $table->foreignId('transaction_id')
                  ->constrained('caisse_transactions')
                  ->onDelete('cascade');

            // Détails du billetage
            // On stocke la valeur faciale (ex: 10000) et la quantité (ex: 5)
            $table->integer('valeur_coupure')->comment('Valeur du billet ou de la pièce');
            $table->integer('quantite')->comment('Nombre d\'unités');
            
            // Champ calculé pour faciliter les requêtes de reporting (valeur * quantite)
            $table->decimal('sous_total', 15, 2);

              // --- INDICATEUR DE TYPE ---
            $table->boolean('is_retrait_distance')->default(false);

            // --- PIÈCES JOINTES (Stockage des chemins de fichiers) ---
            $table->string('pj_demande_retrait')->nullable();
            $table->string('pj_procuration')->nullable();

            // --- ACTEURS ET RESPONSABILITÉS ---
            // On lie au gestionnaire qui a apporté le dossier
            $table->unsignedBigInteger('gestionnaire_id')->nullable();
            // On lie au Chef d'Agence qui doit valider
            $table->unsignedBigInteger('chef_agence_id')->nullable();

            // --- WORKFLOW DE VALIDATION ---
            // Valeurs possibles : 'NORMAL' (retrait direct), 'EN_ATTENTE_CA', 'VALIDE_CA', 'REJETE_CA'
            $table->string('statut_workflow')->default('NORMAL')->after('statut');
            $table->timestamp('date_validation_ca')->nullable()->after('statut_workflow');
            $table->text('motif_rejet_ca')->nullable()->after('date_validation_ca');

            // --- CONTRAINTES D'INTÉGRITÉ ---
            $table->foreign('gestionnaire_id')->references('id')->on('gestionnaires')->onDelete('set null');
            $table->foreign('chef_agence_id')->references('id')->on('users')->onDelete('set null');

            // Audit
            $table->timestamps();

            // Index pour la performance des rapports de caisse
            $table->index(['transaction_id', 'valeur_coupure']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_billetages');
    }
};