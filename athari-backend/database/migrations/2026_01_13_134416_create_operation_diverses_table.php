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
        // Vérification de sécurité pour éviter l'erreur "Table already exists"
        if (!Schema::hasTable('operation_diverses')) {
            Schema::create('operation_diverses', function (Blueprint $table) {
                $table->id();
                $table->string('numero_od', 20)->unique()->comment('Format: OD-YYYY-MM-NNNN');
                
                // Assurez-vous que la table 'agencies' existe bien sous ce nom exact
                $table->foreignId('agence_id')->constrained('agencies');
                
                $table->date('date_operation');
                $table->date('date_valeur')->nullable()->comment('Date de valeur');
                $table->enum('type_operation', ['DEPOT','RETRAIT','VIREMENT','FRAIS','COMMISSION','REGULARISATION','AUTRE']);
                $table->string('libelle');
                $table->text('description')->nullable();
                $table->decimal('montant', 15, 2);
                $table->enum('devise', ['FCFA','EURO','DOLLAR','POUND'])->default('FCFA');
                
                // Liens comptables vers le plan comptable
                $table->foreignId('compte_debit_id')->constrained('plan_comptable');
                $table->foreignId('compte_credit_id')->constrained('plan_comptable');
                
                // Pour virements entre comptes clients
                $table->foreignId('compte_client_debiteur_id')->nullable()->constrained('comptes');
                $table->foreignId('compte_client_crediteur_id')->nullable()->constrained('comptes');
                
                // Validation et workflow
                $table->enum('statut', ['BROUILLON','SAISI','VALIDE','ANNULE','REJETE'])->default('BROUILLON');
                $table->boolean('est_comptabilise')->default(false);
                $table->string('numero_piece', 50)->nullable()->comment('Numéro de pièce comptable');
                
                // Personnes impliquées (Liaison avec la table users)
                $table->foreignId('saisi_par')->constrained('users');
                $table->foreignId('valide_par')->nullable()->constrained('users');
                $table->foreignId('comptabilise_par')->nullable()->constrained('users');
                
                // Dates importantes
                $table->timestamp('date_saisie')->useCurrent();
                $table->timestamp('date_validation')->nullable();
                $table->timestamp('date_comptabilisation')->nullable();
                
                // Pièces justificatives
                $table->enum('justificatif_type', ['FACTURE','QUITTANCE','BON','TICKET','AUTRE'])->nullable();
                $table->string('justificatif_numero', 100)->nullable();
                $table->date('justificatif_date')->nullable();
                $table->string('justificatif_path')->nullable();
                
                // Champs pour suivi et contrôle
                $table->string('reference_client', 100)->nullable();
                $table->string('nom_tiers', 255)->nullable();
                $table->boolean('est_urgence')->default(false);
                $table->text('motif_rejet')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Index pour la performance des recherches
                $table->index('date_operation');
                $table->index('statut');
                $table->index(['agence_id', 'date_operation']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_diverses');
    }
};