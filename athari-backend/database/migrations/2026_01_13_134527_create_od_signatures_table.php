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
        // Vérification pour éviter l'erreur SQLSTATE[42S01] Table already exists
        if (!Schema::hasTable('od_signatures')) {
            Schema::create('od_signatures', function (Blueprint $table) {
                $table->id();
                
                // Relation avec l'OD (on s'assure que si l'OD est supprimée, les signatures le sont aussi)
                $table->foreignId('operation_diverse_id')
                      ->constrained('operation_diverses')
                      ->onDelete('cascade');

                $table->foreignId('user_id')->constrained('users')->comment('Validateur');
                
                $table->tinyInteger('niveau_validation')->comment('Niveau dans la chaîne de validation');
                $table->string('role_validation', 50)->comment('Rôle du validateur');
                $table->enum('decision', ['APPROUVE','REJETE','EN_ATTENTE'])->default('EN_ATTENTE');
                $table->text('commentaire')->nullable();
                $table->string('signature_path')->nullable()->comment('Chemin signature électronique');
                $table->timestamp('signature_date')->nullable();
                $table->timestamps();
                
                // Contrainte unique pour éviter qu'un même utilisateur signe deux fois le même niveau pour une même OD
                $table->unique(['operation_diverse_id', 'user_id', 'niveau_validation'], 'od_signatures_unique');
                
                // Index pour la performance
                $table->index(['operation_diverse_id', 'niveau_validation']);
                $table->index(['user_id', 'decision']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_signatures');
    }
};