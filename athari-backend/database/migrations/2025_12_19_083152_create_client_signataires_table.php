<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Créer la table séparée pour les signataires
        Schema::create('client_signataires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_morale_id')->constrained('clients_morales')->onDelete('cascade');
            $table->enum('numero_signataire', ['1', '2', '3'])->comment('1=signataire1, 2=signataire2, 3=signataire3');
            
            // Informations personnelles
            $table->string('nom');
            $table->enum('sexe', ['M', 'F'])->nullable();
            
            // Localisation
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->string('lieu_domicile')->nullable();
            $table->string('lieu_dit_domicile')->nullable();
            
            // Contact
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            
            // Documents d'identité
            $table->string('cni')->nullable();
            $table->string('cni_photo_recto')->nullable();
            $table->string('cni_photo_verso')->nullable();
            $table->string('nui')->nullable();
            $table->string('nui_image')->nullable();
            
            // Photos et signatures
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            
            // Photos de localisation
            $table->string('lieu_dit_domicile_photo')->nullable();
            $table->string('photo_localisation_domicile')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('numero_signataire');
            $table->index('cni');
            $table->index('nui');
            $table->index('sexe');
            
            // Contrainte d'unicité : un seul signataire par numéro par client moral
            $table->unique(['client_morale_id', 'numero_signataire'], 'unique_signataire_per_client');
        });

        // Mettre à jour la table clients_morales pour retirer les champs de signataires
        Schema::table('clients_morales', function (Blueprint $table) {
            // Supprimer les anciens champs de signataires si ils existent
            $columnsToDrop = [
                'nom_signataire', 'sexe_signataire', 'ville_signataire', 'quartier_signataire',
                'lieu_domicile_signataire', 'lieu_dit_domicile_signataire', 'lieu_dit_domicile_photo_signataire',
                'photo_localisation_domicile_signataire', 'email_signataire', 'cni_signataire',
                'cni_photo_recto_signataire', 'cni_photo_verso_signataire', 'photo_signataire',
                'signature_signataire', 'nui_signataire', 'nui_image_signataire', 'telephone_signataire',
                'nom_signataire2', 'sexe_signataire2', 'ville_signataire2', 'quartier_signataire2',
                'lieu_domicile_signataire2', 'lieu_dit_domicile_signataire2', 'lieu_dit_domicile_photo_signataire2',
                'photo_localisation_domicile_signataire2', 'email_signataire2', 'cni_signataire2',
                'cni_photo_recto_signataire2', 'cni_photo_verso_signataire2', 'photo_signataire2',
                'signature_signataire2', 'nui_signataire2', 'nui_image_signataire2', 'telephone_signataire2',
                'nom_signataire3', 'sexe_signataire3', 'ville_signataire3', 'quartier_signataire3',
                'lieu_domicile_signataire3', 'lieu_dit_domicile_signataire3', 'lieu_dit_domicile_photo_signataire3',
                'photo_localisation_domicile_signataire3', 'email_signataire3', 'cni_signataire3',
                'cni_photo_recto_signataire3', 'cni_photo_verso_signataire3', 'photo_signataire3',
                'signature_signataire3', 'nui_signataire3', 'nui_image_signataire3', 'telephone_signataire3'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('clients_morales', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Ajouter les champs PDF communs s'ils n'existent pas
            if (!Schema::hasColumn('clients_morales', 'liste_membres_pdf')) {
                $table->string('liste_membres_pdf')->nullable();
            }
            
            if (!Schema::hasColumn('clients_morales', 'demande_ouverture_pdf')) {
                $table->string('demande_ouverture_pdf')->nullable();
            }
            
            if (!Schema::hasColumn('clients_morales', 'formulaire_ouverture_pdf')) {
                $table->string('formulaire_ouverture_pdf')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_signataires');
        
        // Note: La suppression des colonnes ne sera pas restaurée automatiquement
        // car c'est une opération destructrice
    }
};