<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('avis_credits', function (Blueprint $table) {
        // On ajoute la colonne role après le user_id
        if (!Schema::hasColumn('avis_credits', 'role')) {
            $table->string('role')->nullable()->after('user_id');
        }
    });
}

public function down()
{
    Schema::table('avis_credits', function (Blueprint $table) {
        if (Schema::hasColumn('avis_credits', 'role')) {
            $table->dropColumn('role');
        }
    });
}
};
