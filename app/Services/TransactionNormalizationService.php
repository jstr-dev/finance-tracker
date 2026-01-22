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

        Format your response as:
        normalized_name|regex_pattern

        Example input: "AMAZON PRIME*Y898213HD  AMZN.CO.UK/PM"
        Example output: Amazon Prime|AMAZON\s+PRIME.*

        Merchants to normalize:
        {list}

        Respond with one line per merchant in the same order, using the format: normalized_name|regex_pattern
    PROMPT;

    private const CATEGORY_SYSTEM_PROMPT = 'You are a financial category normalization expert. Provide standardized category names from a predefined list and regex patterns.';

    private const CATEGORY_USER_PROMPT = <<<'PROMPT'
        Normalize these transaction categories. For each one, provide:
        1. A standardized category name from this list: {categories}
        2. A regex pattern (without delimiters) that will match similar variations

        Format your response as:
        normalized_category|regex_pattern

        Example input: "General Purchases-Online Purchases"
        Example output: Shopping|General\s+Purchases.*Online

        Categories to normalize:
        {list}

        Respond with one line per category in the same order, using the format: normalized_category|regex_pattern
    PROMPT;

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

            foreach ($normalized as $raw => $data) {
                MerchantNormalization::create([
                    'raw_merchant' => $raw,
                    'normalized_merchant' => $data['normalized'],
                    'regex_pattern' => $data['regex'] ?? null,
                    'detection_method' => 'ai',
                ]);

                $results[$raw] = $data['normalized'];
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

            foreach ($normalized as $raw => $data) {
                CategoryNormalization::create([
                    'raw_category' => $raw,
                    'normalized_category' => $data['normalized'],
                    'regex_pattern' => $data['regex'] ?? null,
                    'detection_method' => 'ai',
                ]);

                $results[$raw] = $data['normalized'];
            }
        }

        return $results;
    }

    protected function normalizeMerchantsViaAI(array $rawMerchants): array
    {
        $gemini = app(GeminiService::class);

        $list = implode("\n", array_map(fn($idx, $m) => ($idx + 1) . ". $m", array_keys($rawMerchants), $rawMerchants));
        $prompt = str_replace('{list}', $list, self::MERCHANT_USER_PROMPT);
        
        $content = $gemini->chat(self::MERCHANT_SYSTEM_PROMPT, $prompt, 0.3);

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
        
        $content = $gemini->chat(self::CATEGORY_SYSTEM_PROMPT, $prompt, 0.3);

        return $this->parseCategoryResponse($content, $rawCategories);
    }

    protected function parseMerchantResponse(string $content, array $rawMerchants): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $results = [];
        
        foreach ($rawMerchants as $idx => $raw) {
            if (!isset($lines[$idx])) {
                throw new RuntimeException("AI response missing merchant at index {$idx}");
            }

            $parts = explode('|', $lines[$idx], 2);
            $results[$raw] = [
                'normalized' => trim($parts[0]),
                'regex' => isset($parts[1]) ? trim($parts[1]) : null,
            ];
        }

        return $results;
    }

    protected function parseCategoryResponse(string $content, array $rawCategories): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $results = [];
        
        foreach ($rawCategories as $idx => $raw) {
            if (!isset($lines[$idx])) {
                throw new RuntimeException("AI response missing category at index {$idx}");
            }

            $parts = explode('|', $lines[$idx], 2);
            $results[$raw] = [
                'normalized' => trim($parts[0]),
                'regex' => isset($parts[1]) ? trim($parts[1]) : null,
            ];
        }

        return $results;
    }
}
