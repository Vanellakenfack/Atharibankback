<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * On ajoute les colonnes ici.
     */
    public function up(): void
    {
        Schema::table('dat_contracts', function (Blueprint $table) {
            // On vérifie si la colonne n'existe pas déjà pour éviter les erreurs
            if (!Schema::hasColumn('dat_contracts', 'statut')) {
                $table->string('statut')->default('EN_ATTENTE')->after('id');
            }
            if (!Schema::hasColumn('dat_contracts', 'montant_actuel')) {
                $table->decimal('montant_actuel', 15, 2)->default(0)->after('capital_initial');
            }
            if (!Schema::hasColumn('dat_contracts', 'interets_cumules')) {
                $table->decimal('interets_cumules', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('dat_contracts', 'date_dernier_calcul')) {
                $table->date('date_dernier_calcul')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     * On définit comment annuler ces changements.
     */
    public function down(): void
    {
        Schema::table('dat_contracts', function (Blueprint $table) {
            $table->dropColumn(['statut', 'montant_actuel', 'interets_cumules', 'date_dernier_calcul']);
        });
    }
};