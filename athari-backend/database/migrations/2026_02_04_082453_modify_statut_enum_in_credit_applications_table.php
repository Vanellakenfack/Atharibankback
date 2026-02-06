<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'CA_VALIDE', 'ASC_VALIDE', 'COMITE', 'APPROUVE', 'REJETE', 'MIS_EN_PLACE', 'success', 'echec') DEFAULT 'SOUMIS'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE credit_applications MODIFY COLUMN statut ENUM('SOUMIS', 'EN_ANALYSE', 'APPROUVE', 'REJETE', 'MIS_EN_PLACE') DEFAULT 'SOUMIS'");
    }
};
