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
        Schema::create('jours_comptables', function (Blueprint $table) {
           $table->id();
            $table->foreignId('agence_id')->constrained('agencies')->onDelete('cascade');
            $table->date('date_du_jour'); // La date de travail actuelle
            $table->date('date_precedente')->nullable(); // Utile pour les reports de solde
            $table->enum('statut', ['OUVERT', 'FERME'])->default('FERME');
            $table->timestamp('ouvert_at')->nullable();
            $table->timestamp('ferme_at')->nullable();
            $table->foreignId('execute_par')->constrained('users'); // Responsable du changement de date
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jours_comptables');
    }
};
