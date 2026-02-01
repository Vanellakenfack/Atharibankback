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
            // 1. Foreign Key Field (Removed 'after' to prevent errors)
            if (!Schema::hasColumn('credit_products', 'chapitre_capital_id')) {
                $table->foreignId('chapitre_capital_id')->nullable()
                    ->constrained('plan_comptable')
                    ->onDelete('set null');
            }

            // 2. Boolean Fields
            if (!Schema::hasColumn('credit_products', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (!Schema::hasColumn('credit_products', 'nouvelle_grille')) {
                $table->boolean('nouvelle_grille')->default(false);
            }

            // 3. Logic and Calculation Fields
            if (!Schema::hasColumn('credit_products', 'logique_calcul')) {
                $table->string('logique_calcul')->nullable();
            }
            
            if (!Schema::hasColumn('credit_products', 'formule_calcul')) {
                $table->text('formule_calcul')->nullable();
            }
            
            if (!Schema::hasColumn('credit_products', 'exemple_calcul')) {
                $table->text('exemple_calcul')->nullable();
            }

            // 4. Description Field
            if (!Schema::hasColumn('credit_products', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_products', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('credit_products', 'chapitre_capital_id')) {
                $table->dropForeign(['chapitre_capital_id']);
                $table->dropColumn('chapitre_capital_id');
            }

            // List of columns to drop
            $columns = [
                'is_active', 
                'nouvelle_grille', 
                'logique_calcul', 
                'formule_calcul', 
                'exemple_calcul', 
                'description'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('credit_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};