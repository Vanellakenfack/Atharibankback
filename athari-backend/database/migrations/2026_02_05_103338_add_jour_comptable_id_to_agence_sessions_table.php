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
    public function up()
    {
        if (!Schema::hasColumn('agence_sessions', 'jour_comptable_id')) {
            Schema::table('agence_sessions', function (Blueprint $table) {
                $table->foreignId('jours_comptable_id')
                      ->nullable()
                      ->after('agence_id')
                      ->constrained('jours_comptables')
                      ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('agence_sessions', 'jour_comptable_id')) {
            // Use raw SQL for safer FK removal - try both possible names
            try {
                DB::statement('ALTER TABLE agence_sessions DROP FOREIGN KEY IF EXISTS agence_sessions_jour_comptable_id_foreign');
            } catch (\Exception $e) {
                // Ignore, FK might not exist or have a different name
            }
            
            try {
                DB::statement('ALTER TABLE agence_sessions DROP FOREIGN KEY IF EXISTS agence_sessions_jour_comptable_id_foreign');
            } catch (\Exception $e) {
                // Ignore, FK might not exist
            }
            
            Schema::table('agence_sessions', function (Blueprint $table) {
                $table->dropColumn('jour_comptable_id');
            });
        }
    }
};
