<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $apiKey = config('services.gemini.api_key');
        
        if (!$apiKey) {
            throw new RuntimeException('Gemini API key not configured');
        }
        
        $this->apiKey = $apiKey;
        $this->model = config('services.gemini.model', 'gemini-2.0-flash-exp');
    }

    public function chat(string $systemPrompt, string $userPrompt, ?array $jsonSchema = null, float $temperature = 0.3): string
    {
        $generationConfig = [
            'temperature' => $temperature,
            'maxOutputTokens' => 2048,
        ];

        // Add structured output configuration if schema provided
        if ($jsonSchema !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseJsonSchema'] = $jsonSchema;
        }

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new RuntimeException('Invalid Gemini API response structure');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
}
