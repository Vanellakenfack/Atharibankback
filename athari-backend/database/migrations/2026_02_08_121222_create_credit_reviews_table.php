<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // On s'assure que la table n'existe pas avant de la créer
        Schema::dropIfExists('credit_reviews');

        Schema::create('credit_reviews', function (Blueprint $table) {
            $table->id();
            
            // On pointe sur credit_applications.id
            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); 

            // Pour savoir qui a donné cet avis (Chef Agence, etc.)
            $table->string('role_at_vote'); 

            $table->enum('decision', ['FAVORABLE', 'DEFAVORABLE', 'RESERVE']);
            $table->text('commentaires')->nullable();
            $table->timestamps();

            // Un utilisateur ne vote qu'une seule fois par dossier
            $table->unique(['credit_application_id', 'user_id'], 'unique_vote_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_reviews');
    }
};