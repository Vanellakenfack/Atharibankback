<?php
// database/migrations/xxxx_xx_xx_create_mouvements_rubriques_mata_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_rubriques_mata', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->enum('rubrique', [
                'SANTÉ',
                'BUSINESS',
                'FETE',
                'FOURNITURE',
                'IMMO',
                'SCOLARITÉ'
            ]);
            
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_rubrique', 15, 2)->comment('Solde de la rubrique après mouvement');
            $table->decimal('solde_global', 15, 2)->comment('Solde global du compte après mouvement');
            
            $table->enum('type_mouvement', ['versement', 'retrait', 'commission', 'interet']);
            $table->string('reference_operation')->nullable();
            
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('compte_id');
            $table->index('rubrique');
            $table->index(['compte_id', 'rubrique']);
            $table->index('type_mouvement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_rubriques_mata');
    }
};