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
        Schema::create('frais_en_attente', function (Blueprint $table) {
            $table->id();
            
            // Référence au compte concerné
            $table->foreignId('compte_id')
                  ->constrained('comptes')
                  ->onDelete('cascade')
                  ->comment('Le compte qui doit de l\'argent');

            $table->decimal('montant', 15, 2)->comment('Montant de la commission non prélevée');
            
            $table->string('type_frais')->default('COMMISSION_MENSUELLE');
            
            // Pour savoir de quel mois date la dette
            $table->integer('mois');
            $table->integer('annee');
            
            $table->enum('statut', ['en_attente', 'recupere'])
                  ->default('en_attente')
                  ->comment('Statut de recouvrement de la dette');

            $table->timestamps();
            
            // Index pour la rapidité des recherches lors des versements
            $table->index(['compte_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frais_en_attente');
    }
};