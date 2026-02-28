<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_transactions_digitales', function (Blueprint $table) {
            // Ajout des colonnes manquantes
            $table->unsignedBigInteger('jours_comptable_id')->nullable()->after('id');
            $table->date('date_comptable')->nullable()->after('jours_comptable_id');
            
            // Index pour les performances des rapports
            $table->index('jours_comptable_id');
            $table->index('date_comptable');
            
            // Relation étrangère (optionnel mais recommandé)
            $table->foreign('jours_comptable_id')
                  ->references('id')
                  ->on('jours_comptables')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_transactions_digitales', function (Blueprint $table) {
            $table->dropForeign(['jours_comptable_id']);
            $table->dropColumn(['jours_comptable_id', 'date_comptable']);
        });
    }
};