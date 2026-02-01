<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('caisses', function (Blueprint $table) {
        if (!Schema::hasColumn('caisses', 'compte_comptable_id')) {
            // On lie la caisse à un ID du plan comptable (ex: l'ID qui porte le numéro 57100000)
            $table->unsignedBigInteger('compte_comptable_id')->nullable();

            // Optionnel : ajouter la contrainte de clé étrangère
            $table->foreign('compte_comptable_id')->references('id')->on('plan_comptable');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            //
        });
    }
};
