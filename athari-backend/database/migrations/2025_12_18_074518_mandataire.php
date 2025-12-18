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
        Schema::create('mandataire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(1);
            $table->enum('gender', ['masculin', 'feminin']);
            $table->string('last_name');
            $table->string('first_name');
            $table->date('birth_date');
            $table->string('birth_place');
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->string('nationality')->default('Camerounaise');
            $table->string('profession')->nullable();
            $table->string('mother_maiden_name')->nullable();
            $table->string('cni_number', 50);
            $table->date('cni_issue_date')->nullable();
            $table->date('cni_expiry_date')->nullable();
            $table->enum('marital_status', ['celibataire', 'marie', 'divorce', 'veuf', 'autres']);
            $table->string('spouse_name')->nullable();
            $table->date('spouse_birth_date')->nullable();
            $table->string('spouse_birth_place')->nullable();
            $table->string('spouse_cni')->nullable();
            $table->text('signature_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandataire');
    }
};
