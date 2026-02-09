<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $columns = [
                'photocopie_cni' => 'string',
                'plan_localisation_domicile' => 'string',
                'description_domicile' => 'text',
                'geolocalisation_domicile' => 'string',
                'photo_domicile_1' => 'string',
                'photo_domicile_2' => 'string',
                'photo_domicile_3' => 'string',
                'description_activite' => 'text',
                'geolocalisation_img' => 'string',
                'photo_activite_1' => 'string',
                'photo_activite_2' => 'string',
                'photo_activite_3' => 'string',
                'lettre_non_remboursement' => 'string'
            ];

            foreach ($columns as $column => $type) {
                // On vérifie si la colonne n'existe pas déjà avant de la créer
                if (!Schema::hasColumn('credit_applications', $column)) {
                    $table->$type($column)->nullable();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $columns = [
                'photocopie_cni', 'plan_localisation_domicile', 'description_domicile',
                'geolocalisation_domicile', 'photo_domicile_1', 'photo_domicile_2',
                'photo_domicile_3', 'description_activite', 'geolocalisation_img',
                'photo_activite_1', 'photo_activite_2', 'photo_activite_3',
                'lettre_non_remboursement'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('credit_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};