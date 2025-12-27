<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dat_contracts', function (Blueprint $table) {
            $table->id();
            
            // Lien vers la table du collègue (supposée nommée 'comptes' ou 'accounts')
            $table->foreignId('account_id')->constrained('comptes')->onDelete('cascade');

            // --- GESTION DES TRANCHES (Règle des 3 versements) ---
            $table->integer('nb_tranches_max')->default(3);
            $table->integer('nb_tranches_actuel')->default(0);
            
            // --- DATES CLÉS ---
            $table->timestamp('date_scellage')->nullable(); // Date du blocage effectif
            $table->timestamp('date_maturite')->nullable(); // Date de fin (ex: +9 mois)

            // --- PARAMÈTRES FINANCIERS (Issus de votre CSV) ---
            $table->decimal('taux_interet_annuel', 8, 4); // Ex: 0.0450 (4.5%)
            $table->decimal('taux_penalite_anticipe', 8, 4)->default(0.1000); // Ex: 0.1 (10%)
            
            // --- CALCULS TEMPS RÉEL ---
            $table->decimal('interets_cumules', 15, 2)->default(0); // Gain actuel du client
            $table->boolean('is_blocked')->default(false); // Statut du blocage
              $table->enum('mode_versement', ['ESCOMPTE', 'CAPITALISATION'])->default('CAPITALISATION');
            $table->decimal('capital_initial', 15, 2)->default(0);
            $table->date('date_dernier_calcul')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dat_contracts');
    }
};