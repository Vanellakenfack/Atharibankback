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
    Schema::table('dat_types', function (Blueprint $table) {
        // Ajout de la colonne manquante
        $table->decimal('taux_penalite', 5, 4)->default(0)->after('taux_interet');
    });
}

public function down(): void
{
    Schema::table('dat_types', function (Blueprint $table) {
        $table->dropColumn('taux_penalite');
    });
}

    /**
     * Reverse the migrations.
     */
   
};
