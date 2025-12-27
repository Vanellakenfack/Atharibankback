<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
{
    Schema::table('dat_types', function (Blueprint $table) {
        // On ajoute les colonnes d'ID pour lier au Plan Comptable
       
    });
}

public function down(): void
{
    Schema::table('dat_types', function (Blueprint $table) {
        $table->dropForeign(['plan_comptable_penalite_id']);
        $table->dropColumn(['plan_comptable_penalite_id']);
    });
}
};
