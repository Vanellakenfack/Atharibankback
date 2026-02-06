<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_penalites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_application_id')
                  ->constrained('credit_applications')
                  ->cascadeOnDelete();

            $table->decimal('montant_penalite', 15, 2);
            $table->string('motif')->nullable();
            $table->date('date_application');

            $table->enum('statut', ['APPLIQUEE', 'ANNULEE'])
                  ->default('APPLIQUEE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_penalites');
    }
};
