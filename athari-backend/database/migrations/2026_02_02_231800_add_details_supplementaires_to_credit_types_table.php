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
        Schema::table('credit_types', function (Blueprint $table) {
            $table->json('details_supplementaires')->nullable()->after('penalite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_types', function (Blueprint $table) {
            $table->dropColumn('details_supplementaires');
        });
    }
};