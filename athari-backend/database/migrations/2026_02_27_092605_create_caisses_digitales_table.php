<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses_digitales', function (Blueprint $table) {
            $table->id();
            
            // Relation avec la caisse physique principale
            $table->foreignId('caisse_id')
                  ->constrained('caisses')
                  ->onDelete('cascade');

            // Spécification de l'opérateur
            $table->enum('operateur', ['ORANGE_MONEY', 'MOBILE_MONEY']);

            // --- SOLDES DIGITAUX ---
            // solde_espece : L'argent physique dédié au digital (dans la pochette)
            $table->decimal('solde_espece', 15, 2)->default(0);
            
            // solde_virtuel_uv : Les unités de valeur (sur le téléphone/SIM)
            $table->decimal('solde_virtuel_uv', 15, 2)->default(0);

            // --- PARAMÈTRES DE CONTRÔLE ---
            $table->decimal('seuil_alerte_espece', 15, 2)->default(50000);
            $table->boolean('est_actif')->default(true);

            // --- SÉCURITÉ & INDEX ---
            // Empêche d'avoir deux lignes "OM" pour la même caisse
            $table->unique(['caisse_id', 'operateur'], 'unique_caisse_operateur');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses_digitales');
    }
};