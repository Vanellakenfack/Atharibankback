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
    Schema::create('mouvements_comptables', function (Blueprint $table) {
        $table->id();
        
        // 1. Quel compte est touché ?
        $table->foreignId('account_id')->constrained('comptes')->onDelete('cascade');

        // 2. Quel montant ?
        $table->decimal('montant', 15, 2); 

        // 3. Le SENS (Le plus important !)
        // DEBIT = l'argent sort du compte du client
        // CREDIT = l'argent entre sur le compte du client
        $table->enum('sens', ['DEBIT', 'CREDIT']);

        // 4. L'explication
        $table->string('libelle'); // ex: "Pénalité DAT 10%" ou "Intérêts 4.5%"
        
        // 5. La date de l'opération
        $table->timestamp('date_operation');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_comptables');
    }
};
