<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            // Ajoutez seulement la colonne urgence d'abord
            if (!Schema::hasColumn('credit_applications', 'urgence')) {
                $table->string('urgence')->nullable()->after('observation');
            }
            
            // Ajoutez aussi garantie et plan_epargne qui manquent
            if (!Schema::hasColumn('credit_applications', 'garantie')) {
                $table->text('garantie')->nullable()->after('urgence');
            }
            
            if (!Schema::hasColumn('credit_applications', 'plan_epargne')) {
                $table->boolean('plan_epargne')->default(false)->after('garantie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->dropColumn(['urgence', 'garantie', 'plan_epargne']);
        });
    }
};