<?php

namespace Tests\Unit\Services;

use App\Models\CategoryNormalization;
use App\Models\DefaultCategory;
use App\Models\MerchantNormalization;
use App\Services\GeminiService;
use App\Services\TransactionNormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TransactionNormalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        DefaultCategory::create(['name' => 'Shopping']);
        DefaultCategory::create(['name' => 'Groceries']);
    }

    public function test_normalizes_merchants_using_exact_match_cache(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'WIDGET CO*ABC123',
            'normalized_merchant' => 'Widget Co',
            'regex_pattern' => null,
            'detection_method' => 'ai',
        ]);

        $service = new TransactionNormalizationService();
        $result = $service->normalizeMerchants(['WIDGET CO*ABC123']);

        $this->assertEquals([
            'WIDGET CO*ABC123' => 'Widget Co',
        ], $result);
    }

    public function test_normalizes_merchants_using_regex_cache(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'WIDGET CO*ABC123',
            'normalized_merchant' => 'Widget Co',
            'regex_pattern' => 'WIDGET\s+CO.*',
            'detection_method' => 'ai',
        ]);

        $service = new TransactionNormalizationService();
        $result = $service->normalizeMerchants(['WIDGET CO*XYZ999']);

        $this->assertEquals([
            'WIDGET CO*XYZ999' => 'Widget Co',
        ], $result);

        $this->assertTrue(MerchantNormalization::where('raw_merchant', 'WIDGET CO*XYZ999')->exists());
    }

    public function test_normalizes_merchants_via_ai_when_not_cached(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn("Widget Co|WIDGET\\s+CO.*");
        });

        $service = new TransactionNormalizationService();
        $result = $service->normalizeMerchants(['WIDGET CO*ABC123']);

        $this->assertEquals([
            'WIDGET CO*ABC123' => 'Widget Co',
        ], $result);

        $cached = MerchantNormalization::where('raw_merchant', 'WIDGET CO*ABC123')->first();
        $this->assertNotNull($cached);
        $this->assertEquals('Widget Co', $cached->normalized_merchant);
        $this->assertEquals('WIDGET\s+CO.*', $cached->regex_pattern);
        $this->assertEquals('ai', $cached->detection_method);
    }

    public function test_normalizes_categories_using_exact_match_cache(): void
    {
        CategoryNormalization::create([
            'raw_category' => 'General Purchases-Online',
            'normalized_category' => 'Shopping',
            'regex_pattern' => null,
            'detection_method' => 'ai',
        ]);

        $service = new TransactionNormalizationService();
        $result = $service->normalizeCategories(['General Purchases-Online']);

        $this->assertEquals([
            'General Purchases-Online' => 'Shopping',
        ], $result);
    }

    public function test_normalizes_categories_using_regex_cache(): void
    {
        CategoryNormalization::create([
            'raw_category' => 'General Purchases-Online',
            'normalized_category' => 'Shopping',
            'regex_pattern' => 'General\s+Purchases.*',
            'detection_method' => 'ai',
        ]);

        $service = new TransactionNormalizationService();
        $result = $service->normalizeCategories(['General Purchases-Retail']);

        $this->assertEquals([
            'General Purchases-Retail' => 'Shopping',
        ], $result);

        $this->assertTrue(CategoryNormalization::where('raw_category', 'General Purchases-Retail')->exists());
    }

    public function test_normalizes_categories_via_ai_when_not_cached(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn("Shopping|General\\s+Purchases.*");
        });

        $service = new TransactionNormalizationService();
        $result = $service->normalizeCategories(['General Purchases-Online']);

        $this->assertEquals([
            'General Purchases-Online' => 'Shopping',
        ], $result);

        $cached = CategoryNormalization::where('raw_category', 'General Purchases-Online')->first();
        $this->assertNotNull($cached);
        $this->assertEquals('Shopping', $cached->normalized_category);
        $this->assertEquals('General\s+Purchases.*', $cached->regex_pattern);
        $this->assertEquals('ai', $cached->detection_method);
    }

    public function test_throws_exception_when_no_default_categories_exist(): void
    {
        DefaultCategory::truncate();

        $service = new TransactionNormalizationService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No default categories configured');

        $service->normalizeCategories(['Test']);
    }

    public function test_batches_multiple_merchants_in_single_ai_call(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn("Widget Co|WIDGET.*\nFood Mart|FOOD.*");
        });

        $service = new TransactionNormalizationService();
        $result = $service->normalizeMerchants(['WIDGET CO STORE', 'FOOD MART 123']);

        $this->assertEquals([
            'WIDGET CO STORE' => 'Widget Co',
            'FOOD MART 123' => 'Food Mart',
        ], $result);
    }

    public function test_inserts_regex_matches_in_batch(): void
    {
        MerchantNormalization::create([
            'raw_merchant' => 'WIDGET*OLD',
            'normalized_merchant' => 'Widget Co',
            'regex_pattern' => 'WIDGET.*',
            'detection_method' => 'ai',
        ]);

        $service = new TransactionNormalizationService();
        $service->normalizeMerchants(['WIDGET*NEW1', 'WIDGET*NEW2', 'WIDGET*NEW3']);

        $this->assertEquals(3, MerchantNormalization::where('detection_method', 'regex')->count());
    }
}

