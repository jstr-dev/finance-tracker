<?php

namespace App\Services;

use App\Models\CategoryNormalization;
use App\Models\DefaultCategory;
use App\Models\MerchantNormalization;
use Cache;
use RuntimeException;

class TransactionNormalizationService
{
    private const MERCHANT_SYSTEM_PROMPT = 'You are a financial data normalization expert. Extract clean merchant names and provide regex patterns to match similar variations.';

    private const MERCHANT_USER_PROMPT = <<<'PROMPT'
        Normalize these merchant names from financial transactions. For each one, provide:
        1. A clean, recognizable merchant/brand name
        2. A regex pattern (without delimiters) that will match similar variations

        Example input: "AMAZON PRIME*Y898213HD  AMZN.CO.UK/PM"
        Example output: {"normalized": "Amazon Prime", "regex": "AMAZON\\s+PRIME.*"}

        Merchants to normalize:
        {list}

        Return an array of objects with "normalized" and "regex" properties, one per merchant in the same order.
    PROMPT;

    private const MERCHANT_JSON_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'normalizations' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'normalized' => [
                            'type' => 'string',
                            'description' => 'Clean, recognizable merchant/brand name',
                        ],
                        'regex' => [
                            'type' => 'string',
                            'description' => 'Regex pattern (without delimiters) to match variations',
                        ],
                    ],
                    'required' => ['normalized', 'regex'],
                ],
            ],
        ],
        'required' => ['normalizations'],
    ];

    private const CATEGORY_SYSTEM_PROMPT = 'You are a financial category normalization expert. Provide standardized category names from a predefined list and regex patterns.';

    private const CATEGORY_USER_PROMPT = <<<'PROMPT'
        Normalize these transaction categories. For each one, provide:
        1. A standardized category name from this list: {categories}
        2. A regex pattern (without delimiters) that will match similar variations

        Example input: "General Purchases-Online Purchases"
        Example output: {"normalized": "Shopping", "regex": "General\\s+Purchases.*Online"}

        Categories to normalize:
        {list}

        Return an array of objects with "normalized" and "regex" properties, one per category in the same order.
    PROMPT;

    private const CATEGORY_JSON_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'normalizations' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'normalized' => [
                            'type' => 'string',
                            'description' => 'Standardized category name from predefined list',
                        ],
                        'regex' => [
                            'type' => 'string',
                            'description' => 'Regex pattern (without delimiters) to match variations',
                        ],
                    ],
                    'required' => ['normalized', 'regex'],
                ],
            ],
        ],
        'required' => ['normalizations'],
    ];

    /**
     * Normalize a batch of merchants.
     * Returns array keyed by raw merchant with normalized values.
     */
    public function normalizeMerchants(array $rawMerchants): array
    {
        $results = [];
        $uncached = [];

        $exactMatches = MerchantNormalization::whereIn('raw_merchant', $rawMerchants)
            ->get()
            ->keyBy('raw_merchant');

        $regexPatterns = MerchantNormalization::whereNotNull('regex_pattern')
            ->get();

        $regexMatchesToCreate = [];

        foreach ($rawMerchants as $raw) {
            if (isset($exactMatches[$raw])) {
                $results[$raw] = $exactMatches[$raw]->normalized_merchant;
                continue;
            }

            $regexMatch = null;
            foreach ($regexPatterns as $pattern) {
                if (preg_match('/' . $pattern->regex_pattern . '/i', $raw)) {
                    $regexMatch = $pattern;
                    break;
                }
            }

            if ($regexMatch) {
                $results[$raw] = $regexMatch->normalized_merchant;
                $regexMatchesToCreate[] = [
                    'raw_merchant' => $raw,
                    'normalized_merchant' => $regexMatch->normalized_merchant,
                    'regex_pattern' => null,
                    'detection_method' => 'regex',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                continue;
            }

            $uncached[] = $raw;
        }

        if (!empty($regexMatchesToCreate)) {
            MerchantNormalization::insert($regexMatchesToCreate);
        }

        if (!empty($uncached)) {
            $normalized = $this->normalizeMerchantsViaAI($uncached);

            // Deduplicate by regex pattern (keep first occurrence)
            $seenPatterns = [];
            $toInsert = [];

            foreach ($normalized as $raw => $data) {
                $pattern = $data['regex'] ?? null;

                // Only insert if regex is null or we haven't seen this pattern yet
                if ($pattern === null || !isset($seenPatterns[$pattern])) {
                    if ($pattern !== null) {
                        $seenPatterns[$pattern] = true;
                    }

                    $toInsert[] = [
                        'raw_merchant' => $raw,
                        'normalized_merchant' => $data['normalized'],
                        'regex_pattern' => $pattern,
                        'detection_method' => 'ai',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $results[$raw] = $data['normalized'];
            }

            if (!empty($toInsert)) {
                // Upsert on regex_pattern to handle race conditions
                MerchantNormalization::upsert(
                    $toInsert,
                    ['regex_pattern'],
                    ['normalized_merchant', 'detection_method', 'updated_at']
                );
            }
        }

        return $results;
    }

    /**
     * Normalize a batch of categories.
     * Returns array keyed by raw category with normalized values.
     */
    public function normalizeCategories(array $rawCategories): array
    {
        $results = [];
        $uncached = [];

        $exactMatches = CategoryNormalization::whereIn('raw_category', $rawCategories)
            ->get()
            ->keyBy('raw_category');

        $regexPatterns = CategoryNormalization::whereNotNull('regex_pattern')
            ->get();

        $regexMatchesToCreate = [];

        foreach ($rawCategories as $raw) {
            if (isset($exactMatches[$raw])) {
                $results[$raw] = $exactMatches[$raw]->normalized_category;
                continue;
            }

            $regexMatch = null;
            foreach ($regexPatterns as $pattern) {
                if (preg_match('/' . $pattern->regex_pattern . '/i', $raw)) {
                    $regexMatch = $pattern;
                    break;
                }
            }

            if ($regexMatch) {
                $results[$raw] = $regexMatch->normalized_category;
                $regexMatchesToCreate[] = [
                    'raw_category' => $raw,
                    'normalized_category' => $regexMatch->normalized_category,
                    'regex_pattern' => null,
                    'detection_method' => 'regex',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                continue;
            }

            $uncached[] = $raw;
        }

        if (!empty($regexMatchesToCreate)) {
            CategoryNormalization::insert($regexMatchesToCreate);
        }

        if (!empty($uncached)) {
            $normalized = $this->normalizeCategoriesViaAI($uncached);

            // Deduplicate by regex pattern (keep first occurrence)
            $seenPatterns = [];
            $toInsert = [];

            foreach ($normalized as $raw => $data) {
                $pattern = $data['regex'] ?? null;

                // Only insert if regex is null or we haven't seen this pattern yet
                if ($pattern === null || !isset($seenPatterns[$pattern])) {
                    if ($pattern !== null) {
                        $seenPatterns[$pattern] = true;
                    }

                    $toInsert[] = [
                        'raw_category' => $raw,
                        'normalized_category' => $data['normalized'],
                        'regex_pattern' => $pattern,
                        'detection_method' => 'ai',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $results[$raw] = $data['normalized'];
            }

            if (!empty($toInsert)) {
                // Upsert on regex_pattern to handle race conditions
                CategoryNormalization::upsert(
                    $toInsert,
                    ['regex_pattern'],
                    ['normalized_category', 'detection_method', 'updated_at']
                );
            }
        }

        return $results;
    }

    protected function normalizeMerchantsViaAI(array $rawMerchants): array
    {
        $gemini = app(GeminiService::class);
        $list = implode("\n", array_map(fn($idx, $m) => ($idx + 1) . ". $m", array_keys($rawMerchants), $rawMerchants));
        $prompt = str_replace('{list}', $list, self::MERCHANT_USER_PROMPT);
        $content = $gemini->chat(self::MERCHANT_SYSTEM_PROMPT, $prompt, self::MERCHANT_JSON_SCHEMA, 0.3);

        return $this->parseMerchantResponse($content, $rawMerchants);
    }

    protected function normalizeCategoriesViaAI(array $rawCategories): array
    {
        $gemini = app(GeminiService::class);
        $availableCategories = Cache::remember('default_categories', 60 * 60, function () {
            return DefaultCategory::pluck('name')
                ->implode(', ');
        });

        if (empty($availableCategories)) {
            throw new RuntimeException('No default categories configured');
        }

        $list = implode("\n", array_map(fn($idx, $c) => ($idx + 1) . ". $c", array_keys($rawCategories), $rawCategories));
        $prompt = str_replace(['{categories}', '{list}'], [$availableCategories, $list], self::CATEGORY_USER_PROMPT);

        $content = $gemini->chat(self::CATEGORY_SYSTEM_PROMPT, $prompt, self::CATEGORY_JSON_SCHEMA, 0.3);

        return $this->parseCategoryResponse($content, $rawCategories);
    }

    protected function parseMerchantResponse(string $content, array $rawMerchants): array
    {
        $data = json_decode($content, true);

        if (!isset($data['normalizations']) || !is_array($data['normalizations'])) {
            throw new RuntimeException('Invalid merchant normalization response structure');
        }

        $normalizations = $data['normalizations'];

        if (count($normalizations) !== count($rawMerchants)) {
            throw new RuntimeException(sprintf(
                'Merchant normalization count mismatch: expected %d, got %d',
                count($rawMerchants),
                count($normalizations)
            ));
        }

        $results = [];
        $rawMerchantsList = array_values($rawMerchants);

        foreach ($normalizations as $idx => $item) {
            $raw = $rawMerchantsList[$idx];
            $results[$raw] = [
                'normalized' => $item['normalized'] ?? '',
                'regex' => $item['regex'] ?? null,
            ];
        }
        
        return $results;
    }

    protected function parseCategoryResponse(string $content, array $rawCategories): array
    {
        $data = json_decode($content, true);

        if (!isset($data['normalizations']) || !is_array($data['normalizations'])) {
            throw new RuntimeException('Invalid category normalization response structure');
        }

        $normalizations = $data['normalizations'];

        if (count($normalizations) !== count($rawCategories)) {
            throw new RuntimeException(sprintf(
                'Category normalization count mismatch: expected %d, got %d',
                count($rawCategories),
                count($normalizations)
            ));
        }

        $results = [];
        $rawCategoriesList = array_values($rawCategories);

        foreach ($normalizations as $idx => $item) {
            $raw = $rawCategoriesList[$idx];
            $results[$raw] = [
                'normalized' => $item['normalized'] ?? '',
                'regex' => $item['regex'] ?? null,
            ];
        }
        
        return $results;
    }
}
