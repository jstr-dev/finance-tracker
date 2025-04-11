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
        Schema::dropIfExists('user_investments');
        Schema::create('user_investments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('connection_id');
            $table->foreign('connection_id')->references('id')->on('user_connections')->onDelete('cascade');

            $table->string('ticker');
            $table->string('name')->nullable();
            $table->decimal('amount', 32, 16);
            $table->decimal('average_price', 32, 16);
            $table->decimal('current_price', 32, 16);
            $table->decimal('current_value', 32, 16)->storedAs('amount * current_price');
            $table->string('currency')->default('GBP');
            $table->date('synced_at');

            $table->unique(['user_id', 'connection_id', 'ticker', 'synced_at']);

            $table->index(['user_id', 'connection_id', 'synced_at', 'ticker'], 'search');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_investments');
    }
};
