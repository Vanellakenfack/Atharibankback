<?php
// database/migrations/2024_01_01_000002_create_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compte', function (Blueprint $table) {
            $table->id();
            
            // Numéro de compte unique (13 caractères + clé)
            $table->string('numero_compte', 14)->unique()->index();
            $table->string('cle_compte', 1);
            
            // Relations
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            $table->foreignId('account_type_id')->constrained('types_compte')->onDelete('restrict');
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('validated_by_ca')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by_aj')->nullable()->constrained('users')->onDelete('set null');
            
            // Soldes
            $table->decimal('solde', 15, 2)->default(0);
            $table->decimal('solde_disponible', 15, 2)->default(0);
            $table->decimal('solde_bloque', 15, 2)->default(0);
            $table->decimal('minimum_compte', 15, 2)->default(0);
            
            // Rubriques MATA BOOST (6 en 1)
            $table->decimal('solde_business', 15, 2)->default(0);
            $table->decimal('solde_sante', 15, 2)->default(0);
            $table->decimal('solde_scolarite', 15, 2)->default(0);
            $table->decimal('solde_fete', 15, 2)->default(0);
            $table->decimal('solde_fournitures', 15, 2)->default(0);
            $table->decimal('solde_immobilier', 15, 2)->default(0);
            
            // Statuts et blocages
            $table->enum('statut', ['en_cours', 'actif', 'suspendu', 'cloture', 'bloque'])->default('en_cours');
            $table->enum('statut_validation', ['en_attente', 'valide_ca', 'valide_aj', 'rejete'])->default('en_attente');
            $table->boolean('opposition_debit')->default(true); // Bloqué sur débit à l'ouverture
            $table->boolean('opposition_credit')->default(false);
            $table->string('motif_opposition')->nullable();
            
            // Dates importantes
            $table->date('date_ouverture')->nullable();
            $table->date('date_echeance')->nullable(); // Pour les comptes bloqués
            $table->date('date_cloture')->nullable();
            $table->timestamp('validated_at_ca')->nullable();
            $table->timestamp('validated_at_aj')->nullable();
            
            // Compteur pour les comptes de même nature
            $table->integer('numero_ordre')->default(1);
            
            // Paramètres spécifiques
            $table->boolean('taxable')->default(false);
            $table->string('devise', 3)->default('XAF');
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Index composites
            $table->index(['client_id', 'account_type_id']);
            $table->index(['agency_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte');
    }
};