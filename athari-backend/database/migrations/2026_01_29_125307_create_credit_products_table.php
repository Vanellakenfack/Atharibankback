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
    Schema::create('credit_products', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('nom');
        $table->string('type');

        // Paramètres financiers
        $table->decimal('taux_interet', 5, 2)->default(0);
        $table->decimal('frais_etude', 10, 2)->default(0);
        $table->decimal('frais_mise_en_place', 10, 2)->default(0);
        $table->decimal('penalite_retard', 10, 2)->default(0);

        // Paramètres de durée
        $table->integer('duree_min')->default(0);
        $table->integer('duree_max')->default(0);
        $table->integer('temps_obtention')->default(0);

        // Liens avec le Plan Comptable (Chapitres)
        $table->foreignId('chapitre_capital_id')->nullable()->constrained('plan_comptable'); // Compte principal du crédit
        $table->foreignId('chapitre_interet_id')->nullable()->constrained('plan_comptable'); // Compte de produit (7xx)
        $table->foreignId('chapitre_frais_etude_id')->nullable()->constrained('plan_comptable');
        $table->foreignId('chapitre_penalite_id')->nullable()->constrained('plan_comptable');

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_products');
    }
};
