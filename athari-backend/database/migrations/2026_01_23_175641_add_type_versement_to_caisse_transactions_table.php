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
    Schema::table('caisse_transactions', function (Blueprint $table) {
        // On l'ajoute après le code de la caisse par exemple
        $table->string('type_versement')->after('code_caisse')->nullable(); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            //
        });
    }
};
