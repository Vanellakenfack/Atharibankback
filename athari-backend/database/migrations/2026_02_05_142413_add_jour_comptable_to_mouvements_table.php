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
        // Ajouter la colonne jour_comptable_id uniquement si elle n'existe pas
        if (!Schema::hasColumn('mouvements_comptables', 'jour_comptable_id')) {
            Schema::table('mouvements_comptables', function (Blueprint $table) {
                $table->unsignedBigInteger('jour_comptable_id')->nullable()->after('id');
                $table->foreign('jour_comptable_id')
                    ->references('id')
                    ->on('jours_comptables')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop the foreign key and column
        if (Schema::hasColumn('mouvements_comptables', 'jour_comptable_id')) {
            // Use raw SQL for safer FK removal
            DB::statement('ALTER TABLE mouvements_comptables DROP FOREIGN KEY IF EXISTS mouvements_comptables_jour_comptable_id_foreign');
            Schema::table('mouvements_comptables', function (Blueprint $table) {
                $table->dropColumn('jour_comptable_id');
            });
        }
    }
};
