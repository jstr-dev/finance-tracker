<?php

namespace Tests\Unit\Models;

use App\Models\CategoryNormalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_category_by_exact_match(): void
    {
        CategoryNormalization::create([
            'raw_category' => 'General Purchases-Online',
            'normalized_category' => 'Shopping',
            'regex_pattern' => null,
            'detection_method' => 'ai',
        ]);

        $result = CategoryNormalization::findByExactMatch('General Purchases-Online');

        $this->assertNotNull($result);
        $this->assertEquals('Shopping', $result->normalized_category);
    }

    public function test_returns_null_when_exact_category_match_not_found(): void
    {
        $result = CategoryNormalization::findByExactMatch('NONEXISTENT');

        $this->assertNull($result);
    }

    public function test_finds_category_by_regex_pattern(): void
    {
        CategoryNormalization::create([
            'raw_category' => 'General Purchases-Online',
            'normalized_category' => 'Shopping',
            'regex_pattern' => 'General\s+Purchases.*',
            'detection_method' => 'ai',
        ]);

        $result = CategoryNormalization::findByRegexMatch('General Purchases-Retail');

        $this->assertNotNull($result);
        $this->assertEquals('Shopping', $result->normalized_category);
    }

    public function test_returns_null_when_category_regex_match_not_found(): void
    {
        CategoryNormalization::create([
            'raw_category' => 'Dining-Restaurants',
            'normalized_category' => 'Restaurants',
            'regex_pattern' => 'Dining.*',
            'detection_method' => 'ai',
        ]);

        $result = CategoryNormalization::findByRegexMatch('Transport-Uber');

        $this->assertNull($result);
    }
}
