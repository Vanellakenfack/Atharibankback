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
        Schema::create('od_modele_lignes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modele_id');
            $table->unsignedBigInteger('compte_id');
            $table->enum('sens', ['D', 'C'])->comment('D=Débit, C=Crédit');
            $table->string('libelle', 255);
            $table->decimal('montant_fixe', 15, 2)->nullable()->comment('Montant fixe si applicable');
            $table->decimal('taux', 5, 2)->nullable()->comment('Taux pour calcul variable');
            $table->integer('ordre')->default(0);
            $table->timestamps();

            // Index
            $table->index('compte_id');
            $table->index('modele_id', 'idx_modele_id');

            // Clés étrangères
            $table->foreign('modele_id')
                  ->references('id')
                  ->on('od_modeles')
                  ->onDelete('cascade');
                  
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('plan_comptable')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_modele_lignes');
    }
};