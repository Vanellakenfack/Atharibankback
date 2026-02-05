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
        Schema::create('bilan_journalier_agences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('jour_comptable_id')->constrained('jours_comptables');
    $table->date('date_comptable');
    $table->decimal('total_especes_entree', 15, 2);
    $table->decimal('total_especes_sortie', 15, 2);
    $table->decimal('solde_theorique_global', 15, 2);
    $table->decimal('solde_reel_global', 15, 2);
    $table->decimal('ecart_global', 15, 2);
    $table->json('resume_caisses'); // Détails de chaque caisse pour archive
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
