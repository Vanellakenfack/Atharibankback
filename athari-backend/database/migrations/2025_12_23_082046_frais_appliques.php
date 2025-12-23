<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_appliques', function (Blueprint $table) {
            $table->id();
            
            // Références
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->foreignId('parametrage_frais_id')->constrained('parametrage_frais')->onDelete('cascade');
            
            // Informations sur l'application
            $table->date('date_application');
            $table->date('date_prelevement')->nullable();
            $table->decimal('montant_calcule', 15, 2);
            $table->decimal('montant_preleve', 15, 2)->nullable();
            
            // Base de calcul
            $table->decimal('base_calcul_valeur', 15, 2)->nullable()->comment('Valeur sur laquelle le calcul est basé');
            $table->string('methode_calcul')->nullable()->comment('Détails de la méthode de calcul');
            
            // Statut
            $table->enum('statut', [
                'CALCULE',
                'A_PRELEVER',
                'PRELEVE',
                'EN_ATTENTE',
                'ANNULE',
                'ECHEC'
            ])->default('CALCULE');
            
            // Journalisation
            $table->foreignId('operation_id')->nullable()->constrained('operations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Comptabilisation
            $table->foreignId('compte_produit_id')->constrained('plan_comptable');
            $table->foreignId('compte_client_id')->constrained('plan_comptable');
            
            $table->timestamp('date_comptabilisation')->nullable();
            $table->string('reference_comptable')->nullable();
            
            // Erreurs et retry
            $table->text('erreur_message')->nullable();
            $table->integer('tentatives')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('compte_id');
            $table->index('date_application');
            $table->index('statut');
            $table->index('date_prelevement');
            $table->index('reference_comptable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_appliques');
    }
};