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
        $table->foreignId('compte_id')->constrained('comptes'); // Liaison avec votre table existante
        
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

        // Gestion des Désaccords (Workflow)
        $table->string('code_desaccord')->nullable(); // SPRV, SLIV, CHIN, FRME
        $table->enum('statut', ['SAISIE', 'ATTENTE_FORCAGE', 'VALIDE', 'ANNULE'])->default('SAISIE');
        
        $table->foreignId('caissier_id')->constrained('users');
        $table->foreignId('approbateur_id')->nullable()->constrained('users');
        $table->timestamps();
        $table->index('numero_bordereau');

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
