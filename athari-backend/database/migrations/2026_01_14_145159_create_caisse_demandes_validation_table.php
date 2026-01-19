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
            Schema::create('caisse_demandes_validation', function (Blueprint $table) {
        $table->id();
        
        // Références de l'opération
        $table->string('type_operation'); // RETRAIT ou VERSEMENT
        $table->json('payload_data');      // Stocke tout le formulaire (montant, compte, tiers, etc.)
        $table->decimal('montant', 15, 2);
        
        // Acteurs (Relations)
        $table->foreignId('caissiere_id')->constrained('users'); 
        $table->foreignId('assistant_id')->nullable()->constrained('users'); 
        
        // Sécurité du workflow
        $table->string('code_validation', 8)->nullable(); // Le code généré par l'assistant
        $table->enum('statut', ['EN_ATTENTE', 'APPROUVE', 'REJETE', 'EXECUTE', 'ANNULE'])
            ->default('EN_ATTENTE');
        
        // Traçabilité
        $table->text('motif_rejet')->nullable();
        $table->timestamp('date_approbation')->nullable();
        $table->timestamps();
        
        // Index pour la performance du tableau de bord assistant
        $table->index(['statut', 'caissiere_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_demandes_validation');
    }
};
