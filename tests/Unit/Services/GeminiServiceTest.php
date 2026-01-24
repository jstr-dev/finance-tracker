<?php

namespace Tests\Unit\Services;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config(['services.gemini.api_key' => 'test-key']);
        config(['services.gemini.model' => 'gemini-2.0-flash-exp']);
    }

    public function test_throws_exception_when_api_key_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gemini API key not configured');

        new GeminiService();
    }

    public function test_sends_correct_request_to_gemini_api(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Normalized response'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new GeminiService();
        $result = $service->chat('System prompt', 'User prompt', null, 0.5);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'gemini-2.0-flash-exp:generateContent')
                && str_contains($request->url(), 'key=test-key')
                && $request['contents'][0]['parts'][0]['text'] === "System prompt\n\nUser prompt"
                && $request['generationConfig']['temperature'] === 0.5;
        });

        $this->assertEquals('Normalized response', $result);
    }

    public function test_throws_exception_on_api_failure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Error', 500),
        ]);

        $service = new GeminiService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gemini API request failed');

        $service->chat('System', 'User');
    }

    public function test_throws_exception_on_invalid_response_structure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'invalid' => 'structure',
            ]),
        ]);

        $service = new GeminiService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Gemini API response structure');

        $service->chat('System', 'User');
    }
}

