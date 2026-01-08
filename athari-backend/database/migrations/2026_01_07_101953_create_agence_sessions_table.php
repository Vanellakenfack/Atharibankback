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
        Schema::create('agence_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->constrained('agencies')->onDelete('cascade');
            $table->date('date_comptable'); // Date métier de la banque
            $table->enum('statut', ['OU', 'FE'])->default('FE'); // OU=Ouvert, FE=Fermé
            $table->timestamp('heure_ouverture')->nullable();
            $table->timestamp('heure_fermeture')->nullable();
            $table->foreignId('ouvert_par')->constrained('users'); // Chef d'agence
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agence_sessions');
    }
};
