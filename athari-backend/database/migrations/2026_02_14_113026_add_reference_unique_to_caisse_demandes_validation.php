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
    Schema::table('caisse_demandes_validation', function (Blueprint $table) {
        // On ajoute la colonne après le montant, par exemple
        $table->string('reference_unique')->nullable()->after('montant')->index();
    });
}

public function down(): void
{
    Schema::table('caisse_demandes_validation', function (Blueprint $table) {
        $table->dropColumn('reference_unique');
    });
}
};
