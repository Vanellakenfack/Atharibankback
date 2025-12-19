<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_compte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_chapter_id')->constrained()->onDelete('restrict');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('category', ['courant', 'epargne', 'mata_boost', 'collecte', 'dat', 'autres']);
            $table->enum('sub_category', ['a_vue', 'bloque', 'particulier', 'entreprise', 'family', 'classique', 'logement', 'participative', 'garantie'])->nullable();
            $table->decimal('opening_fee', 15, 2)->default(0);
            $table->decimal('monthly_commission', 15, 2)->default(0);
            $table->decimal('withdrawal_fee', 15, 2)->default(0);
            $table->decimal('sms_fee', 15, 2)->default(200);
            $table->decimal('minimum_balance', 15, 2)->default(0);
            $table->decimal('unblocking_fee', 15, 2)->default(0);
            $table->decimal('early_withdrawal_penalty_rate', 5, 2)->default(0);
            $table->decimal('interest_rate', 5, 4)->default(0);
            $table->integer('blocking_duration_days')->nullable();
            $table->boolean('is_remunerated')->default(false);
            $table->boolean('requires_checkbook')->default(false);
            $table->json('mata_boost_sections')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_compte');
    }
};