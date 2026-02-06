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
    Schema::table('credit_pvs', function (Blueprint $table) {
        // On passe la colonne en string 50 pour être large
        $table->string('statut', 50)->change(); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_pvs', function (Blueprint $table) {
            //
        });
    }
};
