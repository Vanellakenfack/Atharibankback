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
        // On ajoute la clé étrangère
        $table->foreignId('dat_type_id')->after('account_id')->constrained('dat_types');
        
        // On s'assure que ces colonnes existent pour stocker la "photo" du taux au moment de la signature
        if (!Schema::hasColumn('dat_contracts', 'taux_interet_annuel')) {
            $table->decimal('taux_interet_annuel', 5, 4)->after('dat_type_id');
        }
        if (!Schema::hasColumn('dat_contracts', 'duree_mois')) {
            $table->integer('duree_mois')->after('taux_interet_annuel');
        }
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
