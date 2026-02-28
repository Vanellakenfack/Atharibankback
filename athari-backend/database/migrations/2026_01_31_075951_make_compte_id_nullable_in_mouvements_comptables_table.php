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
    Schema::table('mouvements_comptables', function (Blueprint $table) {
        // On rend le champ nullable pour éviter l'erreur "doesn't have a default value"
        $table->unsignedBigInteger('compte_id')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('mouvements_comptables', function (Blueprint $table) {
        // En cas de rollback, on le remet en obligatoire
        $table->unsignedBigInteger('compte_id')->nullable(false)->change();
    });
}
};
