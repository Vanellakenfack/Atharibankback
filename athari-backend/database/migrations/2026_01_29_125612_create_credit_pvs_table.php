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
        Schema::create('credit_pvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained('credit_applications')->onDelete('cascade');
            $table->integer('niveau');
            $table->string('numero_pv');
            $table->string('fichier_pdf');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_pvs');
    }
};
