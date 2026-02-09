<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    // 1. On réinitialise temporairement les valeurs qui pourraient poser problème
    // Si une ligne a une valeur bizarre, on la remet à 'SOUMIS'
    DB::table('credit_applications')
        ->whereNotIn('statut', ['SOUMIS', 'EN_ANALYSE', 'APPROUVE', 'REJETE', 'MIS_EN_PLACE'])
        ->update(['statut' => 'SOUMIS']);

    // 2. On applique le changement avec le nouveau choix 'EN_COMITE'
    DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'EN_COMITE', 'APPROUVE', 'REJETE', 'MIS_EN_PLACE') DEFAULT 'SOUMIS'");
}

    public function down(): void
    {
        // En cas de retour en arrière, on remet l'ancienne liste
        DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'APPROUVE', 'REJETE', 'MIS_EN_PLACE') DEFAULT 'SOUMIS'");
    }
};