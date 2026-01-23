# Testing Guidelines

## Framework & Conventions
- PHPUnit (not Pest) with class-based tests extending `Tests\TestCase`
- Method names: `public function test_descriptive_name(): void`
- Use PHPUnit assertions: `$this->assertEquals()`, `$this->assertTrue()`, etc.
- **Never use `assert()` for validation** - it's disabled in tests. Use `if/throw` patterns.
- Use `RefreshDatabase` trait for DB tests

## CSV Import Testing

### Storage & Mocking
```php
Storage::fake('local');
Storage::disk('local')->put('file.csv', $content);

$this->mock(GeminiService::class, function ($mock) {
    $mock->shouldReceive('chat')->andReturn(
        json_encode(['normalizations' => [
            ['normalized' => 'Name', 'regex' => 'PATTERN'],
        ]])
    );
});
```

### Provider & Transaction Types
- Verify `provider_id`, `account_type`, `transaction_type` set correctly
- Test payment detection using reflection for protected `isPayment()`:
```php
$reflection = new \ReflectionClass($service);
$method = $reflection->getMethod('isPayment');
$method->setAccessible(true);
$result = $method->invoke($service, $row);
```
- Include payment transactions in fixtures (negative amounts with payment patterns)

### Patterns
- Use fixture files: `file_get_contents(base_path('tests/fixtures/csv/amex-test.csv'))`
- Ensure mock returns correct array count matching items to normalize
- Test deduplication: AI can return same regex for multiple merchants
- Verify upsert behavior and unique constraints

## Test Data
- Generic names: Acme Store, Widget Co, Food Mart
- Generic locations: Springfield, Riverside, US
- Generic IDs: TX001234567890001
- Never commit real personal data
