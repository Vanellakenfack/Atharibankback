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
        // On vérifie si la colonne n'existe pas déjà pour éviter l'erreur SQL
        if (!Schema::hasColumn('caisses', 'compte_comptable_id')) {
            Schema::table('caisses', function (Blueprint $table) {
                // Définition de la colonne
                $table->unsignedBigInteger('compte_comptable_id')->nullable();
                
                // Définition de la clé étrangère
                $table->foreign('compte_comptable_id')
                      ->references('id')
                      ->on('plan_comptable')
                      ->onDelete('set null'); // Sécurité : si le compte est supprimé, la caisse reste
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            // 1. Supprimer d'abord la clé étrangère (important !)
            $table->dropForeign(['compte_comptable_id']);
            
            // 2. Supprimer la colonne
            $table->dropColumn('compte_comptable_id');
        });
    }
};