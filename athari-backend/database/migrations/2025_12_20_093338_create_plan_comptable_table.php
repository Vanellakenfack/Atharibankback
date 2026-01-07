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
          Schema::create('plan_comptable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories_comptables'); 
            $table->string('code')->unique(); // ex: 37225000
            $table->string('libelle');        // ex: COMPTE MATA BOOST A VUE
            
            // La NATURE est ici car elle est spécifique au compte
            $table->enum('nature_solde', ['DEBIT', 'CREDIT', 'MIXTE']); 
            
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_comptable');
    }
};
