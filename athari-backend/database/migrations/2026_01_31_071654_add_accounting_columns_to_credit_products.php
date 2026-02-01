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
    Schema::table('credit_products', function (Blueprint $table) {
        if (!Schema::hasColumn('credit_products', 'grille_tarification')) {
            $table->json('grille_tarification')->nullable()->after('type');
        }
        
        if (!Schema::hasColumn('credit_products', 'chapitre_interet_id')) {
            $table->foreignId('chapitre_interet_id')->nullable()->constrained('plan_comptable');
        }

        if (!Schema::hasColumn('credit_products', 'chapitre_frais_etude_id')) {
            $table->foreignId('chapitre_frais_etude_id')->nullable()->constrained('plan_comptable');
        }

        if (!Schema::hasColumn('credit_products', 'chapitre_penalite_id')) {
            $table->foreignId('chapitre_penalite_id')->nullable()->constrained('plan_comptable');
        }

        if (!Schema::hasColumn('credit_products', 'chapitre_frais_de_mise_en_place')) {
            $table->foreignId('chapitre_frais_de_mise_en_place')->nullable()->constrained('plan_comptable');
        }
    });
}

public function down(): void
{
    Schema::table('credit_products', function (Blueprint $table) {
        $table->dropColumn([
            'grille_tarification',
            'chapitre_interet_id',
            'chapitre_frais_etude_id',
            'chapitre_penalite_id',
            'chapitre_frais_de_mise_en_place'
        ]);
    });
}
};
