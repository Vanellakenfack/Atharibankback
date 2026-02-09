<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Option 1 : Si vous voulez rester sur un ENUM strict
        // Note: Changez la liste selon vos besoins réels
        DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'EN_COMITE', 'APPROUVE', 'REJETE', 'MISE_EN_PLACE') NOT NULL DEFAULT 'SOUMIS'");
        
        /* // Option 2 (Plus flexible) : Transformer en simple String
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->string('statut', 50)->change();
        });
        */
    }

    public function down(): void
    {
        // En cas de retour en arrière, on remet l'ancienne liste
        DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'EN_COMITE', 'APPROUVE', 'REJETE') NOT NULL DEFAULT 'SOUMIS'");
    }
};