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
        Schema::table('user_connections', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->timestamp('first_sync_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_connections', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('first_sync_at');
        });
    }
};
