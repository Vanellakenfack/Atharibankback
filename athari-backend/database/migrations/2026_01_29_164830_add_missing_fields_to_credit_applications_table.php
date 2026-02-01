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
    Schema::table('credit_applications', function (Blueprint $table) {
        // Ajout des colonnes manquantes détectées dans tes logs
        $table->text('observation')->nullable()->after('taux_interet');
        $table->decimal('autres_revenus', 15, 2)->default(0)->after('revenus_mensuels');
        $table->decimal('depenses_mensuelles', 15, 2)->default(0)->after('autres_revenus');
        $table->decimal('montant_dettes', 15, 2)->default(0)->after('depenses_mensuelles');
        $table->string('description_dettes')->nullable()->after('montant_dettes');
        $table->string('nom_banque')->nullable()->after('description_dettes');
        $table->string('numero_compte')->nullable()->after('nom_banque');
        
        // Correction pour credit_product_id (le rendre nullable si pas toujours envoyé)
        $table->foreignId('credit_product_id')->nullable()->change();
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
