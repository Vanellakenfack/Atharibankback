<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
         public function up()
{
    Schema::create('caisse_transactions', function (Blueprint $table) {
        $table->id();
        $table->string('reference_unique')->unique(); // Pour le lettrage comptable
        $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade'); // Liaison avec votre table existante
        
        // Champs métiers Turbobank
        $table->string('code_agence');
        $table->string('code_guichet');
        $table->string('code_caisse');
        $table->enum('type_flux', ['VERSEMENT', 'RETRAIT', 'ENTREE', 'SORTIE', 'TRANSFERT']);
        
        // Montants précis (décimal 15,2 pour la monnaie)
        $table->decimal('montant_brut', 15, 2);
        $table->decimal('commissions', 15, 2)->default(0);
        $table->decimal('taxes', 15, 2)->default(0);
        $table->boolean('frais_en_compte')->default(false);
        $table->string('origine_fonds')->nullable();
        
        // Numéro de bordereau physique
        $table->string('numero_bordereau')->nullable();
        
        // Type de bordereau (ex: GUICHET, CHÈQUE, TRANSFERT)
        $table->string('type_bordereau')->nullable();
        
        // Dates bancaires
        $table->date('date_operation');
        $table->date('date_valeur');
        $table->date('date_indisponible')->nullable();
        $table->string('type_versement')->after('code_caisse')->nullable(); 
            $table->string('code_validation', 6)->nullable()->after('statut_workflow');

        // Gestion des Désaccords (Workflow)
        $table->string('code_desaccord')->nullable(); // SPRV, SLIV, CHIN, FRME
        $table->enum('statut', ['SAISIE', 'en_attente', 'VALIDE', 'ANNULE','APPROUVE_CA'])->default('SAISIE');
        
        $table->foreignId('caissier_id')->constrained('users')->onDelete('cascade');
        $table->unsignedBigInteger('session_id')->nullable();
        // Optionnel : ajouter la clé étrangère
         $table->foreign('session_id')->references('id')->on('caisse_sessions');
        $table->foreignId('approbateur_id')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
        $table->index('numero_bordereau');
       $table->enum('type_versement', [
            'ESPECE', 
            'ORANGE_MONEY', 
            'MOBILE_MONEY', 
        ])->default('ESPECE')->change();
                
            // Optionnel : Ajouter une colonne pour la référence externe (ID transaction OM/MoMo)
            $table->string('reference_externe')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transactions');
    }
};
