<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🔐 Sécurité : éviter double création
        if (Schema::hasTable('frais_applications')) {
            return;
        }

        Schema::create('frais_applications', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            // === RÉFÉRENCES ===
            $table->foreignId('compte_id')
                ->constrained('comptes')
                ->cascadeOnDelete();

            $table->foreignId('type_compte_id')
                ->constrained('types_comptes')
                ->cascadeOnDelete();


            // === TYPE DE FRAIS ===
            $table->enum('type_frais', [
                'OUVERTURE',
                'CARNET',
                'RENOUVELLEMENT',
                'PERTE_CARNET',
                'COMMISSION_MENSUELLE',
                'COMMISSION_RETRAIT',
                'COMMISSION_SMS',
                'INTERET_CREDITEUR',
                'FRAIS_DEBLOCAGE',
                'PENALITE_RETRAIT',
                'CLOTURE_ANTICIPE',
                'MINIMUM_COMPTE'
            ]);

            // === MONTANTS ===
            $table->decimal('montant_base', 15, 2)->nullable();
            $table->decimal('taux_applique', 5, 2)->nullable();
            $table->decimal('montant_frais', 15, 2);

            // === COMPTABILITÉ ===
            $table->foreignId('chapitre_debit_id')
                ->constrained('plan_comptable')
                ->restrictOnDelete();

            $table->foreignId('chapitre_credit_id')
                ->constrained('plan_comptable')
                ->restrictOnDelete();

            $table->string('numero_piece', 50)->nullable();

            // === STATUT ===
            $table->enum('statut', ['EN_ATTENTE', 'APPLIQUE', 'ANNULE'])
                ->default('APPLIQUE');

            $table->date('date_application');
            $table->date('date_valeur');

            // === CONTEXTE ===
            $table->string('periode_reference', 20)->nullable();
            $table->json('details')->nullable();

            // === AUDIT ===
            $table->foreignId('applique_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // === INDEX ===
            $table->index(['compte_id', 'date_application']);
            $table->index(['type_frais', 'date_application']);
            $table->index('periode_reference');
            $table->index('statut');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_applications');
    }
};
