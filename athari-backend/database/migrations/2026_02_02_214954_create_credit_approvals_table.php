<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_approvals', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('compte_id')
                  ->constrained('comptes')
                  ->cascadeOnDelete();

            // Décision
            $table->enum('avis', [
                'APPROUVE',
                'REJETE',
                'AJOURNE'
            ]);

            $table->text('commentaire')->nullable();

            // Workflow de validation
            $table->string('niveau_validation')->nullable(); 
            // ex: AAR, COMITE_CREDIT, DIRECTION

            $table->enum('statut', [
                'BROUILLON',
                'VALIDE',
                'ANNULE'
            ])->default('BROUILLON');

            $table->date('decision_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_approvals');
    }
};
