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
        Schema::create('compte', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 17)->unique();
            $table->string('account_key', 2)->nullable();
            $table->string('full_account_number', 20)->unique()->nullable();
            $table->foreignId('client_id')->constrained()->onDelete('restrict');
            $table->foreignId('account_type_id')->constrained()->onDelete('restrict');
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('restrict');
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('available_balance', 20, 2)->default(0);
            $table->decimal('minimum_balance_amount', 15, 2)->default(0);
            $table->decimal('overdraft_limit', 15, 2)->default(0);
            $table->enum('status', ['pending', 'pending_validation', 'active', 'blocked', 'closed', 'dormant'])->default('pending');
            $table->boolean('debit_blocked')->default(true);
            $table->boolean('credit_blocked')->default(false);
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->date('blocking_end_date')->nullable();
            $table->text('blocking_reason')->nullable();
            $table->json('mata_boost_balances')->nullable();
            $table->boolean('documents_complete')->default(false);
            $table->boolean('notice_accepted')->default(false);
            $table->foreignId('collector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('validated_by_ca')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at_ca')->nullable();
            $table->foreignId('validated_by_aj')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at_aj')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['account_type_id', 'status']);
            $table->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compte');
    }
};
