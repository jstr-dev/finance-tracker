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
        Schema::create('connections', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->string('description');
            $table->string('image');

            $table->primary('id');
        });

        DB::table('connections')->insert([
            'id' => 'trading212',
            'name' => 'Trading212',
            'description' => 'Connect your Trading212 account to see how your investments effect your wealth.',
            'image' => 'connections/trading212.svg'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
