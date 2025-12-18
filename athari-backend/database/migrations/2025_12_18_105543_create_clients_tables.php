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
        // 1. TABLE PRINCIPALE : L'identité bancaire commune
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Le numéro client unique à 9 chiffres (ex: 001000001)
            $table->string('num_client', 9)->unique()->index();
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('restrict');    
              $table->enum('type_client', ['physique', 'morale']);
            
            // Champs de contact & localisation communs aux deux formulaires
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->string('adresse_ville');
            $table->string('adresse_quartier');
            $table->string('bp')->nullable();
            $table->string('pays_residence')->default('Cameroun');

            // Champs de gestion interne
            $table->string('gestionnaire')->nullable();
            $table->string('profil')->nullable();
            $table->boolean('taxable')->default(false);
            $table->boolean('interdit_chequier')->default(false);
            $table->decimal('solde_initial', 15, 2)->default(0);
            
            $table->timestamps();
        });

        // 2. TABLE DÉTAILS PHYSIQUES : Spécificités du premier formulaire
        Schema::create('clients_physiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            $table->string('nom_prenoms');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable();
            $table->string('photo')->nullable();

            
            // Pièces d'identité
            $table->string('cni_numero')->unique();
            $table->date('cni_delivrance')->nullable();
            $table->date('cni_expiration')->nullable();
            
            // Filiation & Profession
            $table->string('nom_pere')->nullable();
            $table->string('nom_mere')->nullable();
         $table->string('nationalite_pere')->nullable();

            $table->string('nationalite_mere')->nullable();

            $table->string('profession')->nullable();
            $table->string('employeur')->nullable();

            // Situation Familiale
            $table->string('situation_familiale')->nullable();
            $table->string('regime_matrimonial')->nullable();
            $table->string('nom_conjoint')->nullable();
            $table->date('date_naissance_conjoint')->nullable();
             $table->string('cni_conjoint')->unique()->nullable();

            $table->string('profession_conjoint')->nullable();
            $table->decimal('salaire', 15, 2)->nullable();
            $table->string('tel_conjoint')->nullable();

            $table->timestamps();
        });

        // 3. TABLE DÉTAILS MORALES : Spécificités du formulaire entreprise
        Schema::create('clients_morales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            $table->string('raison_sociale');
            $table->string('sigle')->nullable();
            $table->string('forme_juridique'); // SA, SARL, ETS...
            
            // Identifiants fiscaux et légaux
            $table->string('rccm')->unique();
            $table->string('nui')->unique(); // Numéro Identifiant Unique
            
            // Représentation
            $table->string('immobiliere');
            $table->string('autres_biens')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients_morales');
        Schema::dropIfExists('clients_physiques');
        Schema::dropIfExists('clients');
    }
};