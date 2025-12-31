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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('idempotency_key')->unique();

            $table->foreignId('source_account_id')
                ->constrained('accounts');

            $table->foreignId('destination_account_id')
                ->nullable()
                ->constrained('accounts');

            $table->decimal('amount', 15, 2);

            $table->enum('type', ['credit', 'debit', 'transfer']);
            $table->enum('status', ['pending', 'processed', 'failed'])
                ->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
