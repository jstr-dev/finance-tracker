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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_transactions', function (Blueprint $table) {
            // Basic fields
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Transaction details
            $table->text('payee');
            $table->text('merchant')->nullable();
            $table->text('category')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('transaction_date');

            // Amount fields
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');

            // Address fields
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();

            // Transaction metadata
            $table->unsignedBigInteger('import_id')->nullable();

            $table->timestamp('imported_at')->nullable();
            $table->string('transaction_id')->unique();
            $table->json('payload')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'transaction_date']);

            $table->foreign('import_id')
                ->references('id')
                ->on('imports')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transactions');
        Schema::dropIfExists('imports');
    }
};
