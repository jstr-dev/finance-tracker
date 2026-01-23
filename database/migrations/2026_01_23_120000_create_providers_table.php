<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // credit_card, bank, investment, crypto
            $table->timestamps();
        });

        // Seed initial providers
        DB::table('providers')->insert([
            [
                'code' => 'amex',
                'name' => 'American Express',
                'type' => 'credit_card',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'monzo',
                'name' => 'Monzo',
                'type' => 'bank',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'trading212',
                'name' => 'Trading 212',
                'type' => 'investment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
