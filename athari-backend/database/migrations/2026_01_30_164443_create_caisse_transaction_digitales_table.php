<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::create('caisse_transactions_digitales', function (Blueprint $table) {
        $table->id();
        
        // On lie à la table principale des transactions
        $table->foreignId('caisse_transaction_id')
              ->constrained('caisse_transactions')
              ->onDelete('cascade');

        // Référence unique de l'opérateur (Orange/MTN) pour éviter les doublons
        $table->string('reference_operateur')->unique(); 
        $table->string('telephone_client')->nullable();
        $table->string('operateur'); // Ex: ORANGE_MONEY
        
        $table->decimal('commission_agent', 15, 2)->default(0);
        $table->json('metadata')->nullable(); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transaction_digitales');
    }
};
