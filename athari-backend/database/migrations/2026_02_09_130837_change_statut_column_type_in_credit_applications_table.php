<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            // On passe la colonne en string(50) pour accepter 'EN_COMITE_COMPLET'
            $table->string('statut', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            // En cas de rollback, on peut revenir à une taille plus petite ou à l'ancien type
            $table->string('statut', 20)->change();
        });
    }
};