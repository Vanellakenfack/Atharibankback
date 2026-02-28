<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liste des tables du module OD à mettre à jour
     */
    private array $tables = [
        'operation_diverses',
        'od_historique',
        'od_signatures',
        'od_workflow'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Ajout sécurisé : on vérifie si la colonne n'existe pas déjà
                    if (!Schema::hasColumn($tableName, 'jours_comptable_id')) {
                        $table->unsignedBigInteger('jours_comptable_id')->nullable()->index();
                    }
                    
                    if (!Schema::hasColumn($tableName, 'date_comptable')) {
                        $table->date('date_comptable')->nullable()->index();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['jours_comptable_id', 'date_comptable']);
                });
            }
        }
    }
};