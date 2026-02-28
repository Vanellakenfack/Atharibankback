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
        Schema::table('clients', function (Blueprint $table) {
            // Ajout des colonnes demandées par votre erreur SQL
            // On les place après 'agency_id' pour garder une table organisée
            $table->unsignedBigInteger('jours_comptable_id')->nullable()->after('agency_id');
            $table->date('date_comptable')->nullable()->after('jours_comptable_id');

            // Si vous voulez garantir l'intégrité des données avec la table des journées
            // $table->foreign('jours_comptable_id')->references('id')->on('jours_comptables')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Suppression des colonnes en cas de rollback
            $table->dropColumn(['jours_comptable_id', 'date_comptable']);
        });
    }
};