<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_application_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('opinion', ['approuve', 'rejete', 'en_attente']);
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // Clés étrangères
            $table->foreign('credit_application_id')
                  ->references('id')->on('credit_applications')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
