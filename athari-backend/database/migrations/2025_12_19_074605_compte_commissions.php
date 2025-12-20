<?php
// database/migrations/2024_01_01_000006_create_account_commissions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compte_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('compte')->onDelete('restrict');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions_compte')->onDelete('set null');
            
            $table->enum('type_commission', [
                'commission_mensuelle',
                'commission_retrait',
                'commission_sms',
                'frais_tenue_compte',
                'frais_deblocage',
                'penalite_retrait_anticipe'
            ]);
            
            $table->decimal('montant', 15, 2);
            $table->decimal('base_calcul', 15, 2)->nullable(); // Montant sur lequel la commission est calculée
            $table->integer('mois');
            $table->integer('annee');
            
            $table->enum('statut', ['en_attente', 'preleve', 'en_attente_solde'])->default('en_attente');
            $table->string('compte_produit'); // Compte comptable de destination
            $table->string('compte_attente')->nullable(); // 47120 - Produits à recevoir
            
            $table->timestamp('preleve_at')->nullable();
            $table->timestamps();
            
            $table->index(['account_id', 'mois', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_commissions');
    }
};