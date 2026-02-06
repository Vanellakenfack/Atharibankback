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
            $table->decimal('interet_total', 15, 2)->default(0)->after('taux_interet');
            $table->decimal('montant_total', 15, 2)->default(0)->after('frais_dossier');
            $table->decimal('penalite_par_jour', 15, 2)->nullable()->after('montant_total');
            $table->decimal('frais_etude', 15, 2)->nullable()->after('frais_dossier');
            $table->string('calcul_details')->nullable()->after('penalite_par_jour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->dropColumn(['interet_total', 'montant_total', 'penalite_par_jour', 'frais_etude', 'calcul_details']);
        });
    }
};