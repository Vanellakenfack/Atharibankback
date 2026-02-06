<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avis_credits', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Avis
            $table->enum('opinion', [
                'FAVORABLE',
                'DEFAVORABLE',
                'RESERVE'
            ]);

            $table->text('commentaire')->nullable();
            $table->text('recommandation')->nullable();

            // Analyse
            $table->integer('score_risque')->nullable();

            // Workflow
            $table->string('niveau_avis')->nullable(); 
            // ex: AAR, CHEF_AGENCE, COMITE

            $table->enum('statut', [
                'BROUILLON',
                'SOUMIS',
                'VALIDE'
            ])->default('BROUILLON');

            $table->date('date_avis')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_credits');
    }
};
