<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
        {
            Schema::table('dat_contracts', function (Blueprint $table) {
                // On stocke l'ID du plan comptable, pas le code en texte
                $table->unsignedBigInteger('plan_comptable_interet_id')->nullable()->after('mode_versement');
                $table->unsignedBigInteger('plan_comptable_penalite_id')->nullable()->after('plan_comptable_interet_id');

                // Définition des clés étrangères
                $table->foreign('plan_comptable_interet_id')->references('id')->on('plan_comptable');
                $table->foreign('plan_comptable_penalite_id')->references('id')->on('plan_comptable');
            });
        }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_contracts', function (Blueprint $table) {
            //
        });
    }
};
