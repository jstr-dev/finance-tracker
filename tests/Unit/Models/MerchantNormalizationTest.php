<?php

namespace Tests\Unit\Models;

use App\Models\MerchantNormalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_merchant_by_exact_match(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'WIDGET CO*ABC123',
            'normalized_merchant' => 'Widget Co',
            'regex_pattern' => null,
            'detection_method' => 'ai',
        ]);

        $result = MerchantNormalization::findByExactMatch('WIDGET CO*ABC123');

        $this->assertNotNull($result);
        $this->assertEquals('Widget Co', $result->normalized_merchant);
    }

    public function test_returns_null_when_exact_match_not_found(): void
    {
        $result = MerchantNormalization::findByExactMatch('NONEXISTENT');

        $this->assertNull($result);
    }

    public function test_finds_merchant_by_regex_pattern(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'WIDGET CO*ABC123',
            'normalized_merchant' => 'Widget Co',
            'regex_pattern' => 'WIDGET\s+CO.*',
            'detection_method' => 'ai',
        ]);

        $result = MerchantNormalization::findByRegexMatch('WIDGET CO*XYZ999');

        $this->assertNotNull($result);
        $this->assertEquals('Widget Co', $result->normalized_merchant);
    }

    public function test_returns_null_when_regex_match_not_found(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'FOOD MART STORES',
            'normalized_merchant' => 'Food Mart',
            'regex_pattern' => 'FOOD\s+MART.*',
            'detection_method' => 'ai',
        ]);

        $result = MerchantNormalization::findByRegexMatch('SHOP RITE*ABC');

        $this->assertNull($result);
    }

    public function test_regex_match_is_case_insensitive(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'COFFEE SHOP DOWNTOWN',
            'normalized_merchant' => 'Coffee Shop',
            'regex_pattern' => 'coffee\s+shop',
            'detection_method' => 'ai',
        ]);

        $result = MerchantNormalization::findByRegexMatch('COFFEE SHOP MAIN ST');

        $this->assertNotNull($result);
        $this->assertEquals('Coffee Shop', $result->normalized_merchant);
    }
}
