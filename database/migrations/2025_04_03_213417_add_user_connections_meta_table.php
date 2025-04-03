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
        Schema::create('user_connection_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_connection_id');
            $table->string('key');
            $table->json('value');
            $table->timestamps();  

            $table->foreign('user_connection_id')->references('id')->on('user_connections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_connection_meta');
    }
};
