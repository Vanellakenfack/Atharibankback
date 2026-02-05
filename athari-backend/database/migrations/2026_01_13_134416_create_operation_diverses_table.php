<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_diverses', function (Blueprint $table) {
            $table->id();
            $table->string('numero_od', 20)->unique()->comment('Format: OD-YYYY-MM-NNNN');
            $table->foreignId('agence_id')->constrained('agencies')->onDelete('restrict');
            $table->date('date_operation');
            $table->date('date_valeur')->nullable()->comment('Date de valeur');
            $table->enum('type_operation', ['DEPOT','RETRAIT','VIREMENT','FRAIS','COMMISSION','REGULARISATION','AUTRE']);
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->decimal('montant', 15, 2);
            $table->enum('devise', ['FCFA','EURO','DOLLAR','POUND'])->default('FCFA');
            
            // Liens comptables
            $table->foreignId('compte_debit_id')->constrained('plan_comptable')->onDelete('restrict');
            $table->foreignId('compte_credit_id')->constrained('plan_comptable')->onDelete('restrict');
            
            // Pour virements entre comptes clients
            $table->foreignId('compte_client_debiteur_id')->nullable()->constrained('comptes')->onDelete('set null');
            $table->foreignId('compte_client_crediteur_id')->nullable()->constrained('comptes')->onDelete('set null');
            
            // Validation et workflow
            $table->enum('statut', ['BROUILLON','SAISI','VALIDE','ANNULE','REJETE'])->default('BROUILLON');
            $table->boolean('est_comptabilise')->default(false);
            $table->string('numero_piece', 50)->nullable()->comment('Numéro de pièce comptable');
            
            // Personnes impliquées
            $table->foreignId('saisi_par')->constrained('users')->onDelete('restrict')->comment('User qui a saisi l\'OD');
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null')->comment('User qui a validé');
            $table->foreignId('comptabilise_par')->nullable()->constrained('users')->onDelete('set null')->comment('User qui a comptabilisé');
            
            // Dates importantes
            $table->timestamp('date_saisie')->useCurrent();
            $table->timestamp('date_validation')->nullable();
            $table->timestamp('date_comptabilisation')->nullable();
            
            // Pièces justificatives
            $table->enum('justificatif_type', ['FACTURE','QUITTANCE','BON','TICKET','AUTRE'])->nullable();
            $table->string('justificatif_numero', 100)->nullable();
            $table->date('justificatif_date')->nullable();
            $table->string('justificatif_path')->nullable()->comment('Chemin fichier justificatif');
            
            // Champs pour suivi et contrôle
            $table->string('reference_client', 100)->nullable()->comment('Référence fournie par le client');
            $table->string('nom_tiers', 255)->nullable()->comment('Nom du tiers concerné');
            $table->boolean('est_urgence')->default(false)->comment('OD urgente');
            $table->text('motif_rejet')->nullable()->comment('Si OD rejetée');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('numero_od');
            $table->index('date_operation');
            $table->index('statut');
            $table->index(['agence_id', 'date_operation']);
            $table->index(['saisi_par', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_diverses');
    }
};