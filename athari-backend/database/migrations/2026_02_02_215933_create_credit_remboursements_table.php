<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_remboursements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->decimal('montant', 15, 2);
            $table->date('date_paiement');

            $table->string('mode_paiement');
            $table->string('reference')->nullable();

            $table->enum('statut', ['VALIDE', 'ANNULE'])
                  ->default('VALIDE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_remboursements');
    }
};
