<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_demandes_validation', function (Blueprint $column) {
            // On ajoute la colonne pour l'ID du jour comptable
            $column->unsignedBigInteger('jours_comptable_id')->nullable()->after('caissiere_id');
            
            // On ajoute aussi la colonne date_comptable qui semble manquer ou être requise
            $column->date('date_comptable')->nullable()->after('jours_comptable_id');
            
            // On ajoute le champ pour le rôle cible (Chef d'Agence, Chef Comptable, etc.)
            $column->string('role_destination')->nullable()->after('statut');

            // Optionnel : Ajout d'une clé étrangère si la table existe
             $column->foreign('jours_comptable_id')->references('id')->on('jours_comptables');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_demandes_validation', function (Blueprint $column) {
            $column->dropColumn(['jours_comptable_id', 'date_comptable', 'role_destination']);
        });
    }
};