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
        Schema::create('operation_diverses', function (Blueprint $table) {
            $table->id();
            $table->string('numero_od', 20)->unique()->comment('Format: OD-YYYY-MM-NNNN');
            $table->unsignedBigInteger('agence_id');
            $table->date('date_operation');
            $table->date('date_valeur')->nullable()->comment('Date de valeur');
            $table->enum('type_operation', ['VIREMENT', 'FRAIS', 'COMMISSION', 'REGULARISATION', 'AUTRE']);
            $table->string('libelle', 255);
            $table->text('description')->nullable();
            $table->decimal('montant', 15, 2);
            $table->enum('devise', ['FCFA', 'EURO', 'DOLLAR', 'POUND'])->default('FCFA');
            $table->unsignedBigInteger('compte_debit_id')->nullable();
            $table->unsignedBigInteger('compte_credit_id')->nullable();
            $table->unsignedBigInteger('compte_client_debiteur_id')->nullable();
            $table->unsignedBigInteger('compte_client_crediteur_id')->nullable();
            $table->enum('statut', [
                'BROUILLON', 'SAISI', 'VALIDE_AGENCE', 
                'VALIDE_COMPTABLE', 'VALIDE', 'ANNULE', 'REJETE'
            ])->default('BROUILLON');
            $table->boolean('est_comptabilise')->default(false);
            $table->string('numero_piece', 50)->nullable()->comment('Numéro de pièce comptable');
            $table->unsignedBigInteger('saisi_par');
            $table->unsignedBigInteger('valide_par')->nullable();
            $table->unsignedBigInteger('comptabilise_par')->nullable();
            $table->timestamp('date_saisie')->useCurrent();
            $table->timestamp('date_validation')->nullable();
            $table->timestamp('date_comptabilisation')->nullable();
            $table->enum('justificatif_type', [
                'FACTURE', 'QUITTANCE', 'BON', 'TICKET', 
                'AUTRE_VIREMENT', 'NOTE_CORRECTION', 'AUTRE'
            ])->nullable();
            $table->string('justificatif_numero', 100)->nullable();
            $table->date('justificatif_date')->nullable();
            $table->string('justificatif_path', 255)->nullable()->comment('Chemin fichier justificatif');
            $table->string('reference_client', 100)->nullable()->comment('Référence fournie par le client');
            $table->string('nom_tiers', 255)->nullable()->comment('Nom du tiers concerné');
            $table->boolean('est_urgence')->default(false)->comment('OD urgente');
            $table->text('motif_rejet')->nullable()->comment('Si OD rejetée');
            $table->timestamps();
            $table->softDeletes();
            $table->string('code_operation', 50)->nullable()->comment("Code d'opération pour le type d'OD");
            $table->string('numero_bordereau', 50)->nullable()->comment('Numéro de bordereau pour les AC');
            $table->boolean('est_collecte')->default(false)->comment("Indique si c'est une opération de collecte");
            $table->unsignedBigInteger('modele_id')->nullable()->comment('ID du modèle utilisé pour la saisie');
            $table->date('date_comptable')->nullable()->comment('Date comptable de l\'opération');
            $table->string('ref_lettrage', 100)->nullable()->comment('Référence de lettrage');
            $table->string('numero_guichet', 50)->nullable()->comment('Code du guichet');
            $table->boolean('est_bloque')->default(false)->comment("Indique si c'est une collecte bloquée");
            $table->enum('type_collecte', ['MATA_BOOST', 'EPARGNE_JOURNALIERE', 'CHARGE', 'AUTRE'])->default('AUTRE');
            $table->enum('sens_operation', ['DEBIT', 'CREDIT'])->default('DEBIT');
            $table->decimal('montant_total', 15, 2)->default(0.00)->comment('Montant total de l\'opération');
            $table->json('comptes_debits_json')->nullable()->comment('JSON des comptes débits avec montants');
            $table->json('comptes_credits_json')->nullable()->comment('JSON des comptes crédits avec montants');
            $table->json('comptes_clients_json')->nullable()->comment('JSON des comptes clients avec montants pour répartition');
            $table->unsignedBigInteger('compte_debit_principal_id')->nullable();
            $table->unsignedBigInteger('compte_credit_principal_id')->nullable();
            

            // Index
            $table->index('numero_od');
            $table->index('date_operation');
            $table->index('statut');
            $table->index(['agence_id', 'date_operation']);
            $table->index(['saisi_par', 'statut']);
            $table->index('code_operation', 'idx_code_operation');
            $table->index('type_collecte', 'idx_type_collecte');

            // Clés étrangères
            $table->foreign('agence_id')->references('id')->on('agencies');
            $table->foreign('compte_debit_id')->references('id')->on('plan_comptable');
            $table->foreign('compte_credit_id')->references('id')->on('plan_comptable');
            $table->foreign('compte_client_debiteur_id')->references('id')->on('comptes');
            $table->foreign('compte_client_crediteur_id')->references('id')->on('comptes');
            $table->foreign('saisi_par')->references('id')->on('users');
            $table->foreign('valide_par')->references('id')->on('users');
            $table->foreign('comptabilise_par')->references('id')->on('users');
            $table->foreign('compte_debit_principal_id')->references('id')->on('plan_comptable');
            $table->foreign('compte_credit_principal_id')->references('id')->on('plan_comptable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_diverses');
    }
};