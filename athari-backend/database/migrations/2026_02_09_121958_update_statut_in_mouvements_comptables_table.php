<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvements_comptables', function (Blueprint $table) {
            // On augmente la taille à 50 pour accepter "COMPTAMILISE" (12 car.)
            // On s'assure aussi que compte_debit_id accepte le NULL si besoin
            $table->string('statut', 50)->change();
            $table->unsignedBigInteger('compte_debit_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mouvements_comptables', function (Blueprint $table) {
            $table->string('statut', 10)->change(); // Retour à l'état précédent
            $table->unsignedBigInteger('compte_debit_id')->nullable(false)->change();
        });
    }
};