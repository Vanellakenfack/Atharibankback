<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            // --- INDICATEUR DE TYPE ---
            $table->boolean('is_retrait_distance')->default(false);

            // --- PIÈCES JOINTES (Stockage des chemins de fichiers) ---
            $table->string('pj_demande_retrait')->nullable();
            $table->string('pj_procuration')->nullable();

            // --- ACTEURS ET RESPONSABILITÉS ---
            // On lie au gestionnaire qui a apporté le dossier
            $table->unsignedBigInteger('gestionnaire_id')->nullable();
            // On lie au Chef d'Agence qui doit valider
            $table->unsignedBigInteger('chef_agence_id')->nullable();

            // --- WORKFLOW DE VALIDATION ---
            // Valeurs possibles : 'NORMAL' (retrait direct), 'EN_ATTENTE_CA', 'VALIDE_CA', 'REJETE_CA'
            $table->string('statut_workflow')->default('NORMAL')->after('statut');
            $table->timestamp('date_validation_ca')->nullable()->after('statut_workflow');
            $table->text('motif_rejet_ca')->nullable()->after('date_validation_ca');

            // --- CONTRAINTES D'INTÉGRITÉ ---
            $table->foreign('gestionnaire_id')->references('id')->on('gestionnaires')->onDelete('set null');
            $table->foreign('chef_agence_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->dropForeign(['gestionnaire_id']);
            $table->dropForeign(['chef_agence_id']);
            $table->dropColumn([
                'is_retrait_distance',
                'pj_demande_retrait',
                'pj_procuration',
                'gestionnaire_id',
                'chef_agence_id',
                'statut_workflow',
                'date_validation_ca',
                'motif_rejet_ca'
            ]);
        });
    }
};