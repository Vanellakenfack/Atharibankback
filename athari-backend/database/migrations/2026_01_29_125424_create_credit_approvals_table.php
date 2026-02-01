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
        Schema::create('credit_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained('credit_applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role');
            $table->enum('avis', ['approuve', 'rejete', 'en_attente']);
            $table->text('commentaire')->nullable();
            $table->integer('niveau');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_approvals');
    }
};
