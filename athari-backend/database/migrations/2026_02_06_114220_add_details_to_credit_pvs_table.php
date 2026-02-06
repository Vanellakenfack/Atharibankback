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
        Schema::table('credit_pvs', function (Blueprint $table) {
            // Montant et durée après arbitrage du comité
            $table->decimal('montant_approuvee', 15, 2)->nullable()->after('montant_approuve');
            $table->integer('duree_approuvee')->nullable()->after('montant_approuvee');
            
            // Informations sur la garantie
            $table->string('nom_garantie')->nullable()->after('duree_approuvee');
            
            // Historique complet des avis en format JSON
            $table->json('details_avis_membres')->nullable()->after('resume_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_pvs', function (Blueprint $table) {
            $table->dropColumn([
                'montant_approuvee',
                'duree_approuvee',
                'nom_garantie',
                'details_avis_membres'
            ]);
        });
    }
};