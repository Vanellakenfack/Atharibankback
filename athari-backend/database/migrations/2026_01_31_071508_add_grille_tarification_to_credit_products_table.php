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
        // On ajoute la colonne en type JSON (ou TEXT si ta DB est vieille) 
        // nullable pour ne pas casser les produits existants
        $table->json('grille_tarification')->after('type')->nullable();
    });
}

public function down(): void
{
    Schema::table('credit_products', function (Blueprint $table) {
        $table->dropColumn('grille_tarification');
    });
}
};
