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
        if (!Schema::hasColumn('credit_applications', 'photocopie_cni')) {
            $table->string('photocopie_cni')->nullable()->after('demande_credit_img');
        }
        
        if (!Schema::hasColumn('credit_applications', 'plan_localisation_domicile')) {
            $table->string('plan_localisation_domicile')->nullable()->after('photocopie_cni');
        }

        if (!Schema::hasColumn('credit_applications', 'description_domicile')) {
            $table->text('description_domicile')->nullable();
        }

        if (!Schema::hasColumn('credit_applications', 'geolocalisation_domicile')) {
            $table->string('geolocalisation_domicile')->nullable();
        }

        // ... Répète ce "if" pour TOUS les autres champs (photo_domicile_1, photo_activite_1, etc.)
        if (!Schema::hasColumn('credit_applications', 'photo_domicile_1')) {
            $table->string('photo_domicile_1')->nullable();
        }
        // Ajoute les autres ici de la même manière
    });
}

public function down()
{
    Schema::table('credit_applications', function (Blueprint $table) {
        $table->dropColumn([
            'photocopie_cni', 'plan_localisation_domicile', 'description_domicile',
            'geolocalisation_domicile', 'photo_domicile_1', 'photo_domicile_2',
            'photo_domicile_3', 'description_activite', 'geolocalisation_img',
            'photo_activite_1', 'photo_activite_2', 'photo_activite_3',
            'lettre_non_remboursement'
        ]);
    });
}
};
