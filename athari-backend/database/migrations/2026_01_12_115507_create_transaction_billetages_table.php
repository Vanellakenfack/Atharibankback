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