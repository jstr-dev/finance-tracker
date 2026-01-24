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
        Schema::create('merchant_normalizations', function (Blueprint $table) {
            $table->id();
            $table->string('raw_merchant', 500)->unique();
            $table->string('normalized_merchant');
            $table->string('regex_pattern', 500)->nullable();
            $table->string('detection_method')->default('ai'); // ai, regex, manual
            $table->timestamps();

            $table->index('raw_merchant');
            $table->index('regex_pattern');
            $table->unique('regex_pattern', 'unique_merchant_regex');
        });

        Schema::create('category_normalizations', function (Blueprint $table) {
            $table->id();
            $table->string('raw_category', 500)->unique();
            $table->string('normalized_category');
            $table->string('regex_pattern', 500)->nullable();
            $table->string('detection_method')->default('ai'); // ai, regex, manual
            $table->timestamps();

            $table->index('raw_category');
            $table->index('regex_pattern');
            $table->unique('regex_pattern', 'unique_category_regex');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_normalizations');
        Schema::dropIfExists('merchant_normalizations');
    }
};
