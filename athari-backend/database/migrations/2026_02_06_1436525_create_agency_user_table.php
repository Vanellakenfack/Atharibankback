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
        Schema::create('agency_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Pour désigner l'agence primaire
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            
            // Assurer qu'un utilisateur n'est assigné qu'une fois par agence
            $table->unique(['user_id', 'agency_id']);
            
            // Index pour les requêtes fréquentes
            $table->index('user_id');
            $table->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_user');
    }
};
