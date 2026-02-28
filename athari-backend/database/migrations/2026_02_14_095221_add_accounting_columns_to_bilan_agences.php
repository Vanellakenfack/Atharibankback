<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bilan_journalier_agences', function (Blueprint $table) {
            // Volet Espèces (Vérification des colonnes existantes ou manquantes)
            if (!Schema::hasColumn('bilan_journalier_agences', 'solde_theorique_global')) {
                $table->decimal('solde_theorique_global', 15, 2)->default(0)->after('total_especes_sortie');
            }

            // Volet Comptable / OD
           $table->json('details_operations')->nullable()->after('ecart_global'); 
            $table->decimal('total_debit_journalier', 15, 2)->default(0)->after('details_operations');
          $table->decimal('total_credit_journalier', 15, 2)->default(0)->after('total_debit_journalier');

            // Volet Audit et Statut
           // $table->json('resume_caisses')->nullable()->after('total_credit_journalier');
            $table->string('statut_cloture')->default('DESEQUILIBRE')->after('resume_caisses');
        });
    }

    public function down(): void
    {
        Schema::table('bilan_journalier_agences', function (Blueprint $table) {
            $table->dropColumn([
                'solde_theorique_global',
                'details_operations',
                'total_debit_journalier',
                'total_credit_journalier',
                'resume_caisses',
                'statut_cloture'
            ]);
        });
    }
};