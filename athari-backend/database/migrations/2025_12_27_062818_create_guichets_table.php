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
        Schema::create('guichets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->constrained('agencies')->onDelete('cascade');
            $table->string('code_guichet')->unique();
            $table->string('nom_guichet');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
           
            // Index supplémentaire pour la clé étrangère (créé automatiquement par Laravel mais explicit ici)
            $table->index('agence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guichets');
    }
};