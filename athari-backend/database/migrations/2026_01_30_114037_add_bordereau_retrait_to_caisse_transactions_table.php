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
        // Ajout du champ - on le met en string pour accepter les numéros avec lettres/tirets
        $table->string('bordereau_rerait')->nullable();
    });
}

public function down()
{
    Schema::table('caisse_transactions', function (Blueprint $table) {
        $table->dropColumn('bordereau_rerait');
    });
}

    /**
     * Reverse the migrations.
     */
   
};
