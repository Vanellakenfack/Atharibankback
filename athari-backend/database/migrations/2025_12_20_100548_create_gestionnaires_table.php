<?php
// database/migrations/2026_01_20_create_gestionnaires_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('gestionnaire_code')->unique();
            $table->string('gestionnaire_nom');
            $table->string('gestionnaire_prenom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('cni_recto')->nullable();
            $table->string('cni_verso')->nullable();
            $table->string('plan_localisation_domicile')->nullable();
            $table->string('signature')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('quartier', 100)->nullable();
            $table->foreignId('agence_id')->constrained('agencies')->onDelete('cascade');
            $table->enum('etat', ['present', 'supprime'])->default('present');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestionnaires');
    }
};