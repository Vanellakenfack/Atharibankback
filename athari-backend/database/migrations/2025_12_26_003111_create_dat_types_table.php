<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     */
      public function up(): void
{
        Schema::create('dat_types', function (Blueprint $table) {
    $table->id();
    $table->string('libelle'); 
    $table->decimal('taux_interet', 5, 4); 
    $table->integer('duree_mois'); 
    $table->foreignId('plan_comptable_chapitre_id')->constrained('plan_comptable');
    
    // --- LIAISONS COMPTABLES (Le Cœur du Système) ---
    // 1. Le Chapitre : Définit la famille comptable (ex: Chapitre 25 pour les dépôts)
    
    // 2. Compte de Charge : Pour le passage des intérêts (ex: 6xx)
    $table->foreignId('plan_comptable_interet_id')->constrained('plan_comptable');
    $table->foreignId('plan_comptable_penalite_id')->constrained('plan_comptable');

    // Paramètres par défaut pour le contrat
    $table->enum('periodicite_defaut', ['M', 'T', 'S', 'A', 'E'])->default('E');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
}

    
    public function down(): void
    {
        Schema::dropIfExists('dat_types');
    }
};
