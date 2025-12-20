<?php
// database/migrations/2024_01_01_000001_create_account_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_compte', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('category', ['courant', 'epargne', 'mata_boost', 'collecte', 'dat', 'autre']);
            $table->enum('sub_category', ['a_vue', 'bloque_3_mois', 'bloque_6_mois', 'bloque_12_mois', 'particulier', 'entreprise', 'association', 'islamique', 'autre'])->nullable();
            
            // Frais et commissions
            $table->decimal('frais_ouverture', 15, 2)->default(0);
            $table->decimal('frais_tenue_compte', 15, 2)->default(0);
            $table->decimal('frais_carnet', 15, 2)->default(0);
            $table->decimal('frais_retrait', 15, 2)->default(0);
            $table->decimal('frais_sms', 15, 2)->default(200);
            $table->decimal('frais_deblocage', 15, 2)->default(0);
            $table->decimal('penalite_retrait_anticipe', 5, 2)->default(0); // Pourcentage
            
            // Commission mensuelle
            $table->decimal('commission_mensuelle_seuil', 15, 2)->nullable(); // Seuil pour différencier les commissions
            $table->decimal('commission_mensuelle_basse', 15, 2)->default(0);
            $table->decimal('commission_mensuelle_haute', 15, 2)->default(0);
            
            // Paramètres du compte
            $table->decimal('minimum_compte', 15, 2)->default(0);
            $table->boolean('remunere')->default(false);
            $table->decimal('taux_interet_annuel', 5, 4)->default(0);
            $table->boolean('est_bloque')->default(false);
            $table->integer('duree_blocage_mois')->nullable();
            $table->boolean('autorise_decouvert')->default(false);
            
            // Paramètres d'arrêté
            $table->enum('periodicite_arrete', ['journalier', 'mensuel', 'trimestriel', 'annuel'])->default('mensuel');
            $table->enum('periodicite_extrait', ['journalier', 'mensuel', 'trimestriel', 'annuel'])->default('mensuel');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_compte');
    }
};