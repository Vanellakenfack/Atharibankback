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
        Schema::create('agencies', function (Blueprint $table) {
           $table->id();
        $table->string('code')->unique(); // Code unique (ex: AGE001)
        $table->string('name');           // Nom complet
        $table->string('short_name', 50); // Nom abrégé (indexé pour la recherche)
        $table->timestamps();
        
        $table->index('short_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
