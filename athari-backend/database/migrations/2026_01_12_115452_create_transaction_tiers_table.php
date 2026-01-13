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
    Schema::create('transaction_tiers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('transaction_id')->constrained('caisse_transactions')->onDelete('cascade');
        $table->string('nom_complet');
        $table->string('type_piece'); // CNI, Passeport, etc.
        $table->string('numero_piece');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_tiers');
    }
};
