<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_types', function (Blueprint $table) {
            $table->id();

            // Identification du type de crédit
            $table->string('credit_characteristics');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('category');

            // Paramètres financiers
            $table->decimal('taux_interet', 5, 2);
            $table->integer('duree'); // durée max (en mois)
            $table->decimal('montant', 15, 2); // montant max autorisé

            // Comptabilité
            $table->foreignId('plan_comptable_id')
      ->nullable()
      ->constrained('plan_comptable') // <-- doit correspondre au nom exact
      ->nullOnDelete();


            $table->string('chapitre_comptable')->nullable();

            // Frais et pénalités
            $table->decimal('frais_dossier', 15, 2)->nullable();
            $table->decimal('penalite', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_types');
    }
};
