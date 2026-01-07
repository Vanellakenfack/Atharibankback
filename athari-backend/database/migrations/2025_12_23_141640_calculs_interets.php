<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculs_interets', function (Blueprint $table) {
            $table->id();
            
            // === RÉFÉRENCES ===
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->foreignId('type_compte_id')->constrained('types_comptes')->cascadeOnDelete();
            
            // === PÉRIODE ===
            $table->date('date_calcul');
            $table->date('periode_debut');
            $table->date('periode_fin');
            
            // === SOLDES ===
            $table->decimal('solde_debut_periode', 15, 2);
            $table->decimal('solde_fin_periode', 15, 2);
            $table->decimal('solde_moyen', 15, 2)->nullable();
            
            // === CALCUL ===
            $table->integer('nombre_jours');
            $table->decimal('taux_annuel', 5, 2)
                ->comment('Taux en %');
            $table->decimal('taux_journalier', 8, 6)
                ->comment('Taux annuel / 365 / 100');
            
            // === RÉSULTAT ===
            $table->decimal('interets_bruts', 15, 2);
            $table->decimal('impots', 15, 2)->default(0)
                ->comment('Si applicable');
            $table->decimal('interets_nets', 15, 2);
            
            // === COMPTABILITÉ ===
            $table->foreignId('chapitre_interet_id')
                ->constrained('plan_comptable')->restrictOnDelete();
            $table->string('numero_piece', 50)->nullable();
            
            // === STATUT ===
            $table->enum('statut', ['CALCULE', 'VERSE', 'ANNULE'])
                ->default('CALCULE');
            $table->date('date_versement')->nullable();
            
            // === MÉTADONNÉES ===
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // === INDEX ===
            $table->index(['compte_id', 'date_calcul']);
            $table->index(['date_calcul', 'statut']);
            $table->index(['periode_debut', 'periode_fin']); // Correction ici
            // $table->unique(['compte_id', 'date_calcul']); // Optionnel - décommentez si nécessaire
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculs_interets');
    }
};