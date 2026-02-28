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
        Schema::table('bilan_journalier_agences', function (Blueprint $table) {
            // On l'ajoute juste après l'ID de la journée pour garder une structure logique
            if (!Schema::hasColumn('bilan_journalier_agences', 'date_comptable')) {
                $table->date('date_comptable')->nullable()->after('jour_comptable_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilan_journalier_agences', function (Blueprint $table) {
            $table->dropColumn('date_comptable');
        });
    }
};