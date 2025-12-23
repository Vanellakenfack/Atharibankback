<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_comptables', function (Blueprint $table) {
            $table->id();
            
            // Références
            $table->foreignId('frais_applique_id')->nullable()->constrained('frais_appliques')->onDelete('set null');
            $table->foreignId('operation_id')->nullable()->constrained('operations')->onDelete('set null');
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');
            
            // Informations mouvement
            $table->date('date_mouvement');
            $table->date('date_valeur')->nullable();
            $table->string('libelle_mouvement');
            $table->text('description')->nullable();
            
            // Comptes
            $table->foreignId('compte_debit_id')->constrained('plan_comptable');
            $table->foreignId('compte_credit_id')->constrained('plan_comptable');
            
            // Montants
            $table->decimal('montant_debit', 15, 2);
            $table->decimal('montant_credit', 15, 2);
            
            // Journal
            $table->string('journal')->default('BANQUE');
            $table->string('numero_piece')->nullable();
            $table->string('reference_operation')->nullable();
            
            // Statut
            $table->enum('statut', ['BROUILLON', 'COMPTABILISE', 'ANNULE', 'REJETE'])->default('BROUILLON');
            $table->boolean('est_pointage')->default(false);
            
            // Validation
            $table->foreignId('validateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_validation')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('date_mouvement');
            $table->index('compte_id');
            $table->index('journal');
            $table->index('statut');
            $table->index('reference_operation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_comptables');
    }
};