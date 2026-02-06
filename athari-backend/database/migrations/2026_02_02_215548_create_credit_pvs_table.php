<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_pvs', function (Blueprint $table) {
            $table->id();

            // Relation
            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            // Identification du PV
            $table->string('numero_pv')->unique();

            // Contenu
            $table->string('fichier_pdf')->nullable();

            // Informations du comité
            $table->date('date_pv')->nullable();
            $table->string('lieu_pv')->nullable();

            // Décision officielle
            $table->decimal('montant_approuve', 15, 2)->nullable();
            $table->text('resume_decision')->nullable();

            // Traçabilité
            $table->foreignId('genere_par')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Workflow
            $table->enum('statut', [
                'BROUILLON',
                'VALIDE',
                'ARCHIVE'
            ])->default('BROUILLON');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_pvs');
    }
};
