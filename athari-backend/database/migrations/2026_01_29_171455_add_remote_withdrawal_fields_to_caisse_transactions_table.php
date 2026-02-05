<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
           

            // 2. Le code secret à 6 chiffres pour la validation finale en caisse
            $table->string('code_validation', 6)->nullable()->after('statut_workflow');

      
        });
    }

    public function down(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'code_validation',
               
            ]);
        });
    }
};