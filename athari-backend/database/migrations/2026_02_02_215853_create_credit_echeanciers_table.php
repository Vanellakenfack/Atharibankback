<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_echeanciers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->integer('numero_echeance');
            $table->date('date_echeance');

            $table->decimal('montant_principal', 15, 2);
            $table->decimal('montant_interet', 15, 2);
            $table->decimal('montant_total', 15, 2);

            $table->enum('statut', ['A_PAYER', 'PAYE', 'EN_RETARD'])
                  ->default('A_PAYER');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_echeanciers');
    }
};
