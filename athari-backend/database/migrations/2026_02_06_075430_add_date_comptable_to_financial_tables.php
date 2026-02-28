<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liste des tables impactant la comptabilité identifiées
     */
    protected $tables = [
        
        // Frais & Intérêts
        'parametrage_frais',
        'frais_commissions',
        'calculs_interets',
        'mouvements_rubriques_mata',
        
        // Documents & Transactions
        'documents_compte',
        'mouvements_comptables',
        
        // Caisse
        
        
        // Operations Diverses
        'operation_diverses',
        'od_historique',
        'od_workflow',
        
        // DAT
        'contratos_dat',
        'contrats_dat',
        'mandataires',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // On ajoute la date_comptable après le champ id ou created_at
                    $table->date('date_comptable')->nullable()->index()->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'date_comptable')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('date_comptable');
                });
            }
        }
    }
};