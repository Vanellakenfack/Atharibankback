<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credit_products', function (Blueprint $table) {
            // Add description column if missing
            if (!Schema::hasColumn('credit_products', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
            
            // Add montant_min column if missing
            if (!Schema::hasColumn('credit_products', 'montant_min')) {
                $table->decimal('montant_min', 15, 2)->default(0)->after('description');
            }
            
            // Add montant_max column if missing
            if (!Schema::hasColumn('credit_products', 'montant_max')) {
                $table->decimal('montant_max', 15, 2)->default(0)->after('montant_min');
            }
            
            // Add grille_tarification column if missing
            if (!Schema::hasColumn('credit_products', 'grille_tarification')) {
                $table->json('grille_tarification')->nullable()->after('montant_max');
            }
            
            // Add is_active column if missing
            if (!Schema::hasColumn('credit_products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('chapitre_penalite_id');
            }
            
            // Add logique_calcul column if missing
            if (!Schema::hasColumn('credit_products', 'logique_calcul')) {
                $table->string('logique_calcul')->nullable()->after('is_active');
            }
            
            // Add formule_calcul column if missing
            if (!Schema::hasColumn('credit_products', 'formule_calcul')) {
                $table->text('formule_calcul')->nullable()->after('logique_calcul');
            }
            
            // Add exemple_calcul column if missing
            if (!Schema::hasColumn('credit_products', 'exemple_calcul')) {
                $table->text('exemple_calcul')->nullable()->after('formule_calcul');
            }
            
            // Add chapitre_frais_de_mise_en_place column if missing
            if (!Schema::hasColumn('credit_products', 'chapitre_frais_de_mise_en_place')) {
                $table->foreignId('chapitre_frais_de_mise_en_place')->nullable()->constrained('plan_comptable')->after('chapitre_penalite_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_products', function (Blueprint $table) {
            // Remove columns only if they exist
            $columns = [
                'description',
                'montant_min',
                'montant_max',
                'grille_tarification',
                'is_active',
                'logique_calcul',
                'formule_calcul',
                'exemple_calcul',
                'chapitre_frais_de_mise_en_place'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('credit_products', $column)) {
                    if ($column === 'chapitre_frais_de_mise_en_place') {
                        $table->dropForeign(['chapitre_frais_de_mise_en_place']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};