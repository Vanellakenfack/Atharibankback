<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {

            // Localisation
            $table->string('geolocalisation_img')->nullable();
            $table->string('plan_localisation_activite_img')->nullable();

            // Activité
            $table->string('photo_activite_img')->nullable();

            // Contact
            $table->string('numero_personne_contact')->nullable();

            // Document officiel
            $table->string('demande_credit_img')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->dropColumn([
                'geolocalisation_img',
                'plan_localisation_activite_img',
                'photo_activite_img',
                'numero_personne_contact',
                'demande_credit_img',
            ]);
        });
    }
};
