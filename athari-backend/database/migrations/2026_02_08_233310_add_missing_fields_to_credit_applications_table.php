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
    Schema::table('credit_applications', function (Blueprint $table) {
        // Ajoute la colonne juste après 'demande_credit_img' par exemple
        $table->string('photocopie_cni')->nullable()->after('demande_credit_img');
        
        // Vérifie si les autres colonnes citées dans ton erreur existent bien, sinon ajoute-les aussi :
        // $table->string('plan_localisation_domicile')->nullable();
        // $table->string('photo_domicile_1')->nullable();
        // ... etc
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            //
        });
    }
};
