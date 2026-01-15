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
    Schema::table('caisse_transactions', function (Blueprint $table) {
        $table->enum('type_versement', [
            'ESPECE', 
            'ORANGE_MONEY', 
            'MOBILE_MONEY', 
        ])->default('ESPECE')->change();
    });
}

public function down(): void
{
    Schema::table('caisse_transactions', function (Blueprint $table) {
        // On revient à string en cas de rollback
        $table->string('type_versement')->default('ESPECE')->change();
    });
}
};
