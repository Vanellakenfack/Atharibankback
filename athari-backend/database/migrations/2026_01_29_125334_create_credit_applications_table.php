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
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('num_dossier')->nullable();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('credit_product_id')->constrained('credit_products')->onDelete('cascade');
            $table->string('type_credit');
            $table->decimal('montant', 15, 2);
            $table->integer('duree');
            $table->decimal('taux_interet', 5, 2);
            $table->string('source_revenus');
            $table->decimal('revenus_mensuels', 15, 2);
            $table->string('statut')->default('en_cours');
            $table->string('code_mise_en_place')->nullable();
            $table->text('note_credit')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            // File paths
            $table->string('demande_credit')->nullable();
            $table->string('plan_epargne')->nullable();
            $table->string('document_identite')->nullable();
            $table->string('photos_4x4')->nullable();
            $table->string('plan_localisation')->nullable();
            $table->string('facture_electricite')->nullable();
            $table->string('casier_judiciaire')->nullable();
            $table->string('historique_compte')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};
