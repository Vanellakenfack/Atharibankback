<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mise à jour de la table transaction_billetages
        Schema::table('transaction_billetages', function (Blueprint $table) {
            $table->unsignedBigInteger('jours_comptable_id')->nullable()->after('sous_total');
            $table->date('date_comptable')->nullable()->after('jours_comptable_id');
        });

        // Mise à jour de la table transaction_tiers
        Schema::table('transaction_tiers', function (Blueprint $table) {
            // On vérifie le dernier champ existant pour placer après (souvent 'prenom' ou 'telephone')
            $table->unsignedBigInteger('jours_comptable_id')->nullable();
            $table->date('date_comptable')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_billetages', function (Blueprint $table) {
            $table->dropColumn(['jours_comptable_id', 'date_comptable']);
        });

        Schema::table('transaction_tiers', function (Blueprint $table) {
            $table->dropColumn(['jours_comptable_id', 'date_comptable']);
        });
    }
};