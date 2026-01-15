<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            // Une caisse appartient à un guichet
            $table->foreignId('guichet_id')->constrained('guichets')->onDelete('cascade');
             $table->unsignedBigInteger('compte_comptable_id')->nullable();
        
            $table->foreign('compte_comptable_id')->references('id')->on('plan_comptable');
            $table->string('code_caisse')->unique(); // Ex: CAISSE-A1
            $table->string('libelle'); // Ex: Caisse Principale
            
            // Suivi du solde en temps réel
            $table->decimal('solde_actuel', 15, 2)->default(0);
            $table->decimal('plafond_max', 15, 2)->nullable(); // Alerte si trop d'espèces
            
            $table->boolean('est_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};