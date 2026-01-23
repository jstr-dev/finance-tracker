<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up duplicate regex patterns in merchant_normalizations
        // Keep the earliest created record for each regex pattern
        DB::statement("
            DELETE t1 FROM merchant_normalizations t1
            INNER JOIN merchant_normalizations t2 
            WHERE t1.id > t2.id 
            AND t1.regex_pattern = t2.regex_pattern
            AND t1.regex_pattern IS NOT NULL
        ");

        // Clean up duplicate regex patterns in category_normalizations
        DB::statement("
            DELETE t1 FROM category_normalizations t1
            INNER JOIN category_normalizations t2 
            WHERE t1.id > t2.id 
            AND t1.regex_pattern = t2.regex_pattern
            AND t1.regex_pattern IS NOT NULL
        ");

        // Add unique constraints only if they don't exist
        if (!$this->constraintExists('merchant_normalizations', 'unique_merchant_regex')) {
            Schema::table('merchant_normalizations', function (Blueprint $table) {
                $table->unique('regex_pattern', 'unique_merchant_regex');
            });
        }

        if (!$this->constraintExists('category_normalizations', 'unique_category_regex')) {
            Schema::table('category_normalizations', function (Blueprint $table) {
                $table->unique('regex_pattern', 'unique_category_regex');
            });
        }
    }

    /**
     * Check if a constraint exists on a table.
     */
    private function constraintExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $exists = DB::selectOne("
            SELECT COUNT(*) as count 
            FROM information_schema.table_constraints 
            WHERE table_schema = ? 
            AND table_name = ? 
            AND constraint_name = ?
        ", [$database, $table, $constraint]);

        return $exists->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_normalizations', function (Blueprint $table) {
            $table->dropUnique('unique_merchant_regex');
        });

        Schema::table('category_normalizations', function (Blueprint $table) {
            $table->dropUnique('unique_category_regex');
        });
    }
};
