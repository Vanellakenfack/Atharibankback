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
       Schema::create('categories_comptables', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique(); // ex: 372
        $table->string('libelle');        // ex: COMPTES DE CHEQUES
        $table->integer('niveau');        // 1: Classe, 2: Rubrique
        $table->foreignId('parent_id')->nullable()->constrained('categories_comptables');

        // Le TYPE est ici car il est général à la rubrique
        $table->enum('type_compte', ['ACTIF', 'PASSIF', 'CHARGE', 'PRODUIT']);
        
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_comptables');
    }
};
