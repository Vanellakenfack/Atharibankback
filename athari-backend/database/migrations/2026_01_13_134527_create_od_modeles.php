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
        Schema::create('od_modeles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Code du modèle');
            $table->string('nom', 255);
            $table->text('description')->nullable();
            $table->string('type_operation', 50);
            $table->string('code_operation', 50)->nullable();
            $table->boolean('est_actif')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('code');
            $table->index('est_actif');
            $table->index('type_operation');
            
            // Clés étrangères (si les tables users existent)
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_modeles');
    }
};