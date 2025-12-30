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
        Schema::table('dat_contracts', function (Blueprint $table) {
            // Ajout du montant actuel pour gérer les versements par tranches
            if (!Schema::hasColumn('dat_contracts', 'montant_actuel')) {
                $table->decimal('montant_actuel', 15, 2)->after('montant_initial');
            }

            // Ajout du mode de versement (Capitalisation ou périodique)
            if (!Schema::hasColumn('dat_contracts', 'mode_versement')) {
                $table->enum('mode_versement', ['CAPITALISATION', 'VERSEMENT_PERIODIQUE'])
                      ->default('CAPITALISATION')
                      ->after('periodicite');
            }

            // Ajout du compteur de tranches
            if (!Schema::hasColumn('dat_contracts', 'nb_tranches_actuel')) {
                $table->integer('nb_tranches_actuel')->default(1)->after('is_blocked');
            }

            // Ajout de la date de clôture réelle (pour les sorties anticipées)
            if (!Schema::hasColumn('dat_contracts', 'date_cloture_reelle')) {
                $table->date('date_cloture_reelle')->nullable()->after('date_maturite');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'montant_actuel', 
                'mode_versement', 
                'nb_tranches_actuel', 
                'date_cloture_reelle'
            ]);
        });
    }
};