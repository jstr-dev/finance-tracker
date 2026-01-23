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
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->after('user_id')
                ->nullable()
                ->constrained('providers')
                ->cascadeOnDelete();

            $table->enum('account_type', ['credit', 'debit', 'investment', 'cash'])
                ->after('provider_id')
                ->default('debit');

            $table->enum('transaction_type', ['purchase', 'payment'])
                ->after('account_type')
                ->default('purchase');

            $table->index('account_type');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropIndex(['account_type']);
            $table->dropIndex(['transaction_type']);
            $table->dropColumn(['provider_id', 'account_type', 'transaction_type']);
        });
    }
};
