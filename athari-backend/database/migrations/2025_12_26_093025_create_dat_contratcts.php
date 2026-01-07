<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
             Schema::create('dat_contracts', function (Blueprint $table) {
    $table->id();
    $table->string('numero_ordre')->unique(); // Attribué par le système
    $table->string('statut')->default('EN_ATTENTE'); 
    
    // --- LIAISONS COMPTES ---
    $table->foreignId('dat_type_id')->constrained('dat_types');
    $table->foreignId('account_id')->constrained('comptes'); // Le compte de stockage DAT (Interne)
    $table->foreignId('client_source_account_id')->constrained('comptes'); // Compte d'origine des fonds
    
    // --- DESTINATIONS (Clés étrangères) ---
    // Par défaut, recevront l'ID du compte courant/épargne du client
    $table->foreignId('destination_interet_id')->nullable()->constrained('comptes');
    $table->foreignId('destination_capital_id')->nullable()->constrained('comptes');

    // --- PARAMÈTRES FINANCIERS ---
    $table->decimal('montant_initial', 15, 2);
    $table->decimal('taux_interet_annuel', 8, 4);
    $table->decimal('taux_penalite_anticipe', 8, 4)->default(0.0000);
    $table->integer('duree_mois');
    
    // --- LOGIQUE TURBOBANK ---
    $table->enum('periodicite', ['M', 'T', 'S', 'A', 'E'])->default('E');
    $table->boolean('is_jours_reels')->default(true); 
    $table->boolean('is_precompte')->default(false); 
       $table->enum('mode_versement', ['CAPITALISATION', 'VERSEMENT_PERIODIQUE']);


    // --- DATES ---
    $table->date('date_execution'); 
    $table->date('date_valeur');    
    $table->date('date_maturite');  
    $table->timestamp('date_scellage')->nullable();
        $table->decimal('montant_actuel', 15, 2)->after('montant_initial');
             $table->decimal('montant_actuel', 15, 2)->after('montant_initial');
           $table->date('date_cloture_reelle')->nullable()->after('date_maturite');


    $table->decimal('interets_cumules', 15, 2)->default(0);
    $table->boolean('is_blocked')->default(true);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('dat_contracts');
    }
};