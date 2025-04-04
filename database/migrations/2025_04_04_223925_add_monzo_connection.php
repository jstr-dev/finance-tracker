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
        DB::table('connections')->insert([
            'id' => 'monzo',
            'name' => 'Monzo',
            'description' => 'Connect your Monzo account to see your account balance.',
            'image' => 'monzo.png'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('connections')->where('id', 'monzo')->delete();
    }
};
