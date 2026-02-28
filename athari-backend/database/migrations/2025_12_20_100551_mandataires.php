<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les mandataires des comptes
 * Permet de gérer jusqu'à 2 mandataires par compte
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandataires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade')->comment('Compte associé');
            
            // Ordre du mandataire
            $table->tinyInteger('ordre')->comment('1 pour mandataire principal, 2 pour secondaire');
            
            // Informations personnelles
            $table->enum('sexe', ['masculin', 'feminin'])->nullable();
            $table->string('nom')->comment('Nom du mandataire');
            $table->string('prenom')->comment('Prénom du mandataire');
            $table->date('date_naissance')->comment('Date de naissance');
            $table->string('lieu_naissance')->comment('Lieu de naissance');
            $table->string('telephone', 20)->comment('Numéro de téléphone');
            $table->text('adresse')->comment('Adresse complète');
            $table->string('nationalite')->comment('Nationalité');
            $table->string('profession')->comment('Profession');
            
            // Informations familiales
            $table->string('nom_jeune_fille_mere')->nullable()->comment('Nom de jeune fille de la mère');
            $table->string('numero_cni', 50)->comment('Numéro CNI');
            $table->enum('situation_familiale', ['marie', 'celibataire', 'autres'])->comment('Situation familiale');
            
            // Informations conjoint (si marié)
            $table->string('nom_conjoint')->nullable()->comment('Nom du conjoint');
            $table->date('date_naissance_conjoint')->nullable()->comment('Date de naissance du conjoint');
            $table->string('lieu_naissance_conjoint')->nullable()->comment('Lieu de naissance du conjoint');
            $table->string('cni_conjoint', 50)->nullable()->comment('CNI du conjoint');
            
            // Signature
            $table->string('signature_path')->nullable()->comment('Chemin vers la signature du mandataire');
            
            $table->timestamps();
            
            // Contrainte: un compte peut avoir max 2 mandataires
            $table->unique(['compte_id', 'ordre']);
            
            $table->index('compte_id');
            $table->index('numero_cni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandataires');
    }
};