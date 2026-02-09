<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReplaceUserIdWithClientIdInCreditApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            // 1. Vérifier si la colonne user_id existe avant de la modifier
            if (Schema::hasColumn('credit_applications', 'user_id')) {
                // Vérifier si la contrainte étrangère existe
                $foreignKeys = DB::select(DB::raw('
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = "credit_applications" 
                    AND COLUMN_NAME = "user_id" 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                '));
                
                // Si une contrainte existe, la supprimer
                if (!empty($foreignKeys)) {
                    $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
                    $table->dropForeign([$constraintName]);
                }
                
                // Supprimer la colonne user_id
                $table->dropColumn('user_id');
            }
            
            // 2. Ajouter la colonne client_id si elle n'existe pas déjà
            if (!Schema::hasColumn('credit_applications', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('numero_demande');
            }
        });
        
        // 3. Ajouter la contrainte étrangère pour client_id
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            // 1. Supprimer la contrainte étrangère de client_id
            $table->dropForeign(['client_id']);
            
            // 2. Supprimer la colonne client_id
            $table->dropColumn('client_id');
            
            // 3. Recréer la colonne user_id
            $table->foreignId('user_id')->nullable()->after('numero_demande');
        });
        
        // 4. Recréer la contrainte étrangère pour user_id
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}