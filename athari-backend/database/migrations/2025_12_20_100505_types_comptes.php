<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les types de comptes
 * Référence: Document "NOMENCLATURE DES COMPTES AUDACE VRAI.pdf"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_comptes', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique()->comment('Code à 2 chiffres du type de compte');
            $table->string('libelle')->comment('Libellé du type de compte');
            
            // Spécificités
            $table->boolean('est_mata')->default(false)->comment('Indique si c\'est un compte MATA (nécessite rubriques)');
            $table->boolean('necessite_duree')->default(false)->comment('Indique si nécessite une durée (DAT/Bloqué)');
            $table->boolean('est_islamique')->default(false)->comment('Compte islamique');
            $table->boolean('actif')->default(true)->comment('Type de compte actif/inactif');
            
            $table->timestamps();
            
            $table->index('code');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_comptes');
    }
};