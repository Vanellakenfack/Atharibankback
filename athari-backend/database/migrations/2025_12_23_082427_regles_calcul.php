<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_calcul', function (Blueprint $table) {
            $table->id();
            
            // Références
            $table->foreignId('type_compte_id')->nullable()->constrained('types_comptes')->onDelete('cascade');
            $table->foreignId('parametrage_frais_id')->nullable()->constrained('parametrage_frais')->onDelete('cascade');
            
            // Identification
            $table->string('code_regle', 50)->unique();
            $table->string('libelle_regle');
            $table->enum('type_regle', ['FRAIS', 'COMMISSION', 'INTERET', 'PENALITE']);
            
            // Conditions
            $table->json('conditions')->nullable()->comment('Conditions en JSON pour application');
            $table->json('declencheurs')->nullable()->comment('Événements déclencheurs');
            
            // Calcul
            $table->enum('methode_calcul', ['FIXE', 'POURCENTAGE', 'ECHELLE', 'SEUIL']);
            $table->json('parametres_calcul')->comment('Paramètres de calcul en JSON');
            
            // Périodicité
            $table->enum('periodicite_calcul', [
                'INSTANTANEE',
                'QUOTIDIENNE',
                'HEBDOMADAIRE',
                'MENSUELLE',
                'TRIMESTRIELLE',
                'ANNUELLE',
                'A_ECHEANCE'
            ])->default('INSTANTANEE');
            
            // Dates
            $table->integer('jour_calcul')->nullable()->comment('Jour du mois pour calcul');
            $table->time('heure_calcul')->nullable();
            $table->string('code_arrête')->nullable()->comment('Code arrêté comptable');
            $table->string('echelle_arrête')->nullable()->comment('Échelle de calcul');
            
            // Comptabilité
            $table->foreignId('compte_produit_defaut_id')->nullable()->constrained('plan_comptable');
            $table->foreignId('compte_attente_defaut_id')->nullable()->constrained('plan_comptable');
            
            // Validation
            $table->boolean('necessite_validation')->default(false);
            $table->json('roles_validation')->nullable()->comment('Rôles autorisés à valider');
            
            // Actif
            $table->boolean('actif')->default(true);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('type_compte_id');
            $table->index('type_regle');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_calcul');
    }
};