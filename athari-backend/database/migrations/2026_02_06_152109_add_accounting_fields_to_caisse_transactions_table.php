<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            // Ajout des colonnes après le caissier_id pour la traçabilité comptable
            $table->date('date_comptable')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->dropColumn([ 'date_comptable']);
        });
    }
};