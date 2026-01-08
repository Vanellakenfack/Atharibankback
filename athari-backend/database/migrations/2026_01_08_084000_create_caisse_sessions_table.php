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
        Schema::create('caisse_sessions', function (Blueprint $table) {
            $table->id();
    $table->foreignId('guichet_session_id')->constrained('guichet_sessions')->onDelete('cascade');
    $table->foreignId('caissier_id')->constrained('users');
    $table->string('code_caisse')->index(); // Essentiel pour getDernierSoldeFermeture()
    $table->decimal('solde_ouverture', 15, 2)->default(0);
    $table->decimal('solde_fermeture', 15, 2)->nullable();
    $table->enum('statut', ['OU', 'FE','RE'])->default('FE');
    $table->json('billetage_ouverture')->nullable(); // Détail des billets (10k, 5k, etc.)

        $table->json('billetage_fermeture')->nullable(); // Important pour l'ajustage final
        
        // Temps
        $table->timestamp('heure_ouverture')->useCurrent();
        $table->timestamp('heure_fermeture')->nullable();
        
  
    $table->decimal('solde_informatique', 15, 2)->default(0); // Ce que le système dit
    $table->decimal('solde_physique', 15, 2)->default(0);     
    $table->text('observations')->nullable(); // Pour les écarts de caisse
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_sessions');
    }
};
