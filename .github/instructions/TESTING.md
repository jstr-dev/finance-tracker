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
- GeminiService returns **plain text** with newline-separated results, NOT JSON
- Merchant format: `"Merchant Name|REGEX_PATTERN\nNext Merchant|NEXT_PATTERN.*"`
- Category format: `"Category|REGEX_PATTERN\nNext Category|NEXT_PATTERN.*"`
- Example:
```php
$this->mock(GeminiService::class, function ($mock) {
    $mock->shouldReceive('chat')
        ->andReturn(
            "Acme Store|ACME.*\nWidget Co|WIDGET.*",  // merchants
            "Shopping|General\\s+Purchases.*\nGroceries|Food.*"  // categories
        );
});
```

### Import service patterns
- Always create `Import` record before calling service->import()
- Set import_id: `$service->setImportId($import->id)`
- Import records track status: processing → completed/failed
- Test both direct service calls AND console commands

### Console command testing
- Use `$this->artisan('command', ['arg' => 'value', '--option' => 'value'])`
- Chain assertions: `->expectsOutput('message')->assertExitCode(0)`
- Verify database state after command execution
- Test failure scenarios (user not found, invalid type, etc.)

## Test data privacy
- Use generic merchant names (Acme Store, Widget Co, Food Mart)
- Use generic locations (Springfield, Riverside, US)
- Use generic transaction IDs (TX001234567890001)
- Never include real personal data in test fixtures
