<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->string('telephone')->unique();
            $table->string('email')->nullable();
            $table->string('adresse_ville');
            $table->string('adresse_quartier');
            $table->string('lieu_dit_domicile')->nullable();
            $table->string('photo_localisation_domicile')->nullable();
            $table->string('lieu_dit_activite')->nullable();
            $table->string('photo_localisation_activite')->nullable();
            $table->string('ville_activite')->nullable();
            $table->string('quartier_activite')->nullable();
            $table->string('bp')->nullable();
            $table->string('nui')->nullable(); // NOUVEAU: NUI dans la table principale
            $table->string('pays_residence')->default('Cameroun');
            $table->string('immobiliere')->nullable();
            $table->string('autres_biens')->nullable();
            
            // Champs de gestion interne
            $table->enum('etat', ['present', 'supprime'])->default('present');
            $table->decimal('solde_initial', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Index
            $table->index('etat');
            $table->index('nui'); // Index sur le NUI
        });

        // 2. TABLE DÉTAILS PHYSIQUES : Spécificités du premier formulaire
        Schema::create('clients_physiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            // Informations personnelles
            $table->string('nom_prenoms');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable();
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->string('nui')->nullable();
            $table->string('niu_image')->nullable(); // NOUVEAU: Photocopie NUI
            
            // Pièces d'identité
            $table->string('cni_numero')->unique();
            $table->date('cni_delivrance')->nullable();
            $table->date('cni_expiration')->nullable();
            $table->string('cni_recto')->nullable();
            $table->string('cni_verso')->nullable();
            
            // Filiation
            $table->string('nom_pere')->nullable();
            $table->string('nom_mere')->nullable();
            $table->string('nationalite_pere')->nullable();
            $table->string('nationalite_mere')->nullable();
            
            // Profession
            $table->string('profession')->nullable();
            $table->string('employeur')->nullable();
            
            // Situation familiale
            $table->string('situation_familiale')->nullable();
            $table->string('regime_matrimonial')->nullable();
            $table->string('nom_conjoint')->nullable();
            $table->date('date_naissance_conjoint')->nullable();
            $table->string('cni_conjoint')->nullable();
            $table->string('profession_conjoint')->nullable();
            $table->decimal('salaire', 15, 2)->nullable();
            $table->string('tel_conjoint')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('nui');
        });

        // 3. TABLE DÉTAILS MORALES : Spécificités du formulaire entreprise/association
        Schema::create('clients_morales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            // Informations sur l'entreprise/association
            $table->string('raison_sociale');
            $table->string('sigle')->nullable();
            $table->string('forme_juridique');
            $table->enum('type_entreprise', ['entreprise', 'association'])->default('entreprise');
            
            // Identifiants légaux
            $table->string('rccm')->nullable(); // MODIFICATION: RCCM n'est plus unique ni required
            $table->string('nui')->nullable();
            
            // Gérants
            $table->string('nom_gerant')->nullable();
            $table->string('telephone_gerant')->nullable();
            $table->string('photo_gerant')->nullable();
            $table->string('nom_gerant2')->nullable();
            $table->string('telephone_gerant2')->nullable();
            $table->string('photo_gerant2')->nullable();
            
            // Signataires (jusqu'à 3)
            $table->string('nom_signataire')->nullable();
            $table->string('telephone_signataire')->nullable();
            $table->string('photo_signataire')->nullable();
            $table->string('signature_signataire')->nullable();
            
            $table->string('nom_signataire2')->nullable();
            $table->string('telephone_signataire2')->nullable();
            $table->string('photo_signataire2')->nullable();
            $table->string('signature_signataire2')->nullable();
            
            $table->string('nom_signataire3')->nullable();
            $table->string('telephone_signataire3')->nullable();
            $table->string('photo_signataire3')->nullable();
            $table->string('signature_signataire3')->nullable();
            
            // Documents juridiques (images)
            $table->string('extrait_rccm_image')->nullable();
            $table->string('titre_patente_image')->nullable();
            $table->string('niu_image')->nullable();
            $table->string('statuts_image')->nullable();
            $table->string('pv_agc_image')->nullable();
            $table->string('attestation_non_redevance_image')->nullable();
            $table->string('proces_verbal_image')->nullable();
            $table->string('registre_coop_gic_image')->nullable();
            $table->string('recepisse_declaration_association_image')->nullable();
            
            // Documents supplémentaires (PDF)
            $table->string('acte_designation_signataires_pdf')->nullable();
            $table->string('liste_conseil_administration_pdf')->nullable();
            $table->string('attestation_conformite_pdf')->nullable(); // NOUVEAU: Attestation de conformité
            
            // Localisation des signataires (images)
            $table->string('plan_localisation_signataire1_image')->nullable();
            $table->string('plan_localisation_signataire2_image')->nullable();
            $table->string('plan_localisation_signataire3_image')->nullable();
            
            // Factures des signataires (images)
            $table->string('facture_eau_signataire1_image')->nullable();
            $table->string('facture_eau_signataire2_image')->nullable();
            $table->string('facture_eau_signataire3_image')->nullable();
            $table->string('facture_electricite_signataire1_image')->nullable();
            $table->string('facture_electricite_signataire2_image')->nullable();
            $table->string('facture_electricite_signataire3_image')->nullable();
            
            // Localisation et factures du siège (images)
            $table->string('plan_localisation_siege_image')->nullable();
            $table->string('facture_eau_siege_image')->nullable();
            $table->string('facture_electricite_siege_image')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('rccm');
            $table->index('nui');
            $table->index('type_entreprise');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients_morales');
        Schema::dropIfExists('clients_physiques');
        Schema::dropIfExists('clients');
    }
};