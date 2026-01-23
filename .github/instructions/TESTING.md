# Testing Guidelines

## Test framework
- Use PHPUnit (not Pest)
- Test method names: `public function test_descriptive_name(): void`
- Class-based tests extending `Tests\TestCase`
- Use `setUp()` and `tearDown()` methods for test fixtures

## Test organization
- Unit tests: `tests/Unit/` - isolated component tests
- Feature tests: `tests/Feature/` - integration tests with database
- Console tests: `tests/Feature/Console/` - artisan command tests
- Keep tests focused and independent

## Assertions
- Use PHPUnit assertions: `$this->assertEquals()`, `$this->assertTrue()`, etc.
- Use type-safe assertions where possible
- Avoid Pest-style `expect()` syntax
- **Never use `assert()` for validation** - it's disabled by default in tests. Use proper `if/throw` patterns instead.

## Database tests
- Use `RefreshDatabase` trait for tests that touch the database
- Seed required data in `setUp()` or test methods
- Clean state between tests

## Testing CSV Imports

### Storage setup
- Use `Storage::fake('local')` in test setUp
- Import services default to 'local' disk (configurable via `setDisk()`)
- Put test CSV files with: `Storage::disk('local')->put('file.csv', $content)`

### Mocking GeminiService
- GeminiService returns **JSON structured output** via `responseJsonSchema`
- Response format: `{"normalizations": [{"normalized": "Name", "regex": "PATTERN"}, ...]}`
- Regex patterns are validated before use (invalid patterns logged and discarded)
- Patterns use `~` delimiter in preg_match: `@preg_match('~' . $pattern . '~i', $text)`
- Always return proper JSON array with correct number of items
- Regex patterns should be valid PHP regex without delimiters
- Example:
```php
$this->mock(GeminiService::class, function ($mock) {
    $mock->shouldReceive('chat')
        ->andReturn(
            json_encode(['normalizations' => [
                ['normalized' => 'Acme Store', 'regex' => 'ACME.*'],
                ['normalized' => 'Widget Co', 'regex' => 'WIDGET.*'],
            ]]),  // First call (merchants)
            json_encode(['normalizations' => [
                ['normalized' => 'Shopping', 'regex' => 'General\\\\s+Purchases.*'],
                ['normalized' => 'Groceries', 'regex' => 'Food.*'],
            ]])   // Second call (categories)
        );
});
```
- Each normalization object must have `normalized` and `regex` properties
- Array length must match number of items being normalized

### Import service patterns
- Use `$service->startImport($user, $path)` - service creates Import record automatically
- Import records track status: processing → completed/failed
- Service returns Import instance with id and status
- Test both direct service calls AND console commands
- For testing async mode, mock the job or test job directly

### Provider and transaction type testing
- Verify `provider_id` is set correctly on imported transactions
- Check `account_type` matches provider (credit for AMEX, debit for banks)
- Test `transaction_type` detection:
  - `purchase` for regular transactions
  - `payment` for payment/refund transactions
- Use reflection to test protected `isPayment()` method:
  ```php
  $reflection = new \ReflectionClass($service);
  $method = $reflection->getMethod('isPayment');
  $method->setAccessible(true);
  $result = $method->invoke($service, $row);
  ```
- Test payment detection patterns (PAYMENT THANK YOU, DIRECT DEBIT, etc.)
- Verify purchase transactions are NOT detected as payments

### Normalization testing
- Normalization tables use unique constraints on regex_pattern
- Test deduplication: AI can return same regex for multiple merchants
- Upsert behavior: existing patterns should be updated, not fail
- Use fixture CSV files in `tests/fixtures/csv/` directory
- Heredoc CSV must NOT be indented (causes leading whitespace)
- Example fixture usage: `file_get_contents(base_path('tests/fixtures/csv/amex-test.csv'))`
- Include payment transactions in test fixtures (negative amounts)

### Console command testing
- Use `$this->artisan('command', ['arg' => 'value', '--option' => 'value'])`
- Chain assertions: `->expectsOutput('message')->assertExitCode(0)`
- Verify database state after command execution
- Test failure scenarios (user not found, invalid type, etc.)
- Verify provider_id, account_type, transaction_type are set correctly

## Test data privacy
- Use generic merchant names (Acme Store, Widget Co, Food Mart)
- Use generic locations (Springfield, Riverside, US)
- Use generic transaction IDs (TX001234567890001)
- Never include real personal data in test fixtures
