<?php
// database/migrations/2024_01_01_000005_create_account_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions_compte', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('account_id')->constrained('compte')->onDelete('restrict');
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->enum('type_transaction', [
                'depot',
                'retrait',
                'virement_entrant',
                'virement_sortant',
                'prelevement_frais',
                'prelevement_commission',
                'prelevement_sms',
                'interet_crediteur',
                'penalite',
                'deblocage',
                'od' // Opération diverse
            ]);
            
            $table->enum('sens', ['debit', 'credit']);
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_avant', 15, 2);
            $table->decimal('solde_apres', 15, 2);
            
            // Pour MATA BOOST - rubrique concernée
            $table->enum('rubrique_mata', ['business', 'sante', 'scolarite', 'fete', 'fournitures', 'immobilier'])->nullable();
            
            $table->string('libelle');
            $table->string('motif')->nullable();
            $table->string('numero_bordereau')->nullable();
            $table->string('numero_piece')->nullable();
            
            // Compte comptable associé
            $table->string('compte_comptable_debit')->nullable();
            $table->string('compte_comptable_credit')->nullable();
            
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'annule'])->default('en_attente');
            $table->date('date_valeur');
            $table->timestamp('validated_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['account_id', 'created_at']);
            $table->index(['type_transaction', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions_compte');
    }
};