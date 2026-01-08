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
        Schema::create('guichet_sessions', function (Blueprint $table) {
           $table->id();
    $table->foreignId('agence_session_id')->constrained('agence_sessions')->onDelete('cascade');
    $table->integer('code_guichet'); 
    $table->enum('statut', ['OU', 'FE'])->default('FE');
    $table->timestamp('heure_ouverture')->nullable();
    $table->timestamp('heure_fermeture')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guichet_sessions');
    }
};
