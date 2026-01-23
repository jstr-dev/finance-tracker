# CSV Import System

## Architecture Overview

The CSV import system provides a flexible framework for importing financial transactions from various providers. It handles file processing, data normalization, and AI-powered merchant/category matching.

## Core Components

### AbstractImportService
Base class for all import implementations located at `app/Services/Import/AbstractImportService.php`.

**Key Features:**
- Chunked processing (100 rows per batch)
- Automatic CSV value trimming
- Storage disk abstraction (defaults to 'local')
- Transaction deduplication by transaction_id
- Import status tracking

**Required Abstract Methods:**
```php
abstract protected function getType(): string;
abstract protected function getRequiredCSVHeaders(): array;
abstract protected function getRowTransactionID(array $row): string;
abstract protected function formatRowForImport(array $row): array;
abstract protected function getProviderId(): int;
abstract protected function getAccountType(): string;
```

**Optional Methods:**
```php
// Override to detect payment transactions (vs purchases)
protected function isPayment(array $row): bool
{
    return false; // default implementation
}
```

**Optional Interface:**
Implement `HasCategory` interface and define `extractCategory(array $row): ?string` for category extraction.

### Import Model
Tracks import job status and metadata (`app/Models/Import.php`).

**Fields:**
- `user_id` - Owner of the import
- `type` - Import service type (e.g., 'amex')
- `status` - processing | completed | failed
- `started_at` - Import start timestamp
- `completed_at` - Import completion timestamp

### ProcessCSVImport Job
Background job for async imports (`app/Jobs/ProcessCSVImport.php`).

Dispatched automatically when using `startImport($user, $path, true)`.

## Import Flow

### 1. Service Initialization
```php
$service = app(AmericanExpressImportService::class);
$service->setDisk('local'); // optional, defaults to 'local'
```

### 2. Start Import
```php
// Synchronous (blocks until complete)
$import = $service->startImport($user, 'path/to/file.csv', false);

// Asynchronous (queued)
$import = $service->startImport($user, 'path/to/file.csv', true);
```

**What happens:**
1. Creates `Import` record with status 'processing'
2. If async: dispatches `ProcessCSVImport` job and returns immediately
3. If sync: calls `processImport()` which:
   - Validates CSV headers
   - Processes file in chunks
   - Normalizes merchants and categories
   - Upserts transactions
   - Updates Import status to 'completed' or 'failed'

### 3. Chunk Processing
For each chunk of 100 rows:
1. Extract all unique merchants and categories
2. Check normalization caches (exact match → regex match)
3. Send remaining unknowns to AI in batches with structured output schema
4. Deduplicate regex patterns (keep first occurrence)
5. Upsert normalizations to database
6. Format transactions with normalized data
7. Add provider metadata (provider_id, account_type, transaction_type)
8. Detect payment transactions using provider-specific patterns
9. Upsert transactions to `user_transactions` table

## Provider System

### Providers Table
Stores information about financial service providers.

**Fields:**
- `id` - Primary key
- `code` - Unique provider code (e.g., 'amex', 'monzo')
- `name` - Display name (e.g., 'American Express')
- `type` - Provider type (credit_card, bank, investment, crypto)

**Seeded Providers:**
- AMEX (code: 'amex', type: 'credit_card')
- Monzo (code: 'monzo', type: 'bank')
- Trading 212 (code: 'trading212', type: 'investment')

### Account Types
Transactions are tagged with account type from Provider constants:
- `credit` - Credit card accounts (debt-based spending)
- `debit` - Bank/checking accounts (real money)
- `investment` - Investment accounts
- `cash` - Cash transactions

### Transaction Types
Transactions are classified as:
- `purchase` - Money spent (default for all non-payment transactions)
- `payment` - Payment toward credit card balance or incoming refund

**Payment Detection:**
Implemented per-provider using `isPayment(array $row): bool` method. Searches description/merchant fields for patterns like:
- `PAYMENT.*THANK YOU`
- `DIRECT DEBIT`
- `PAYMENT RECEIVED`
- `AUTOPAY`

Example (AMEX):
```php
protected function isPayment(array $row): bool
{
    $description = $row['description'] ?? '';
    $payee = $row['appears on your statement as'] ?? '';
    $searchText = $description . ' ' . $payee;

    foreach (self::PAYMENT_PATTERNS as $pattern) {
        if (@preg_match('~' . $pattern . '~i', $searchText)) {
            return true;
        }
    }
    return false;
}
```

## Transaction Normalization

### AI Normalization with Structured Outputs

GeminiService uses **structured outputs** with JSON schema to ensure type-safe, predictable responses.

**Request Format:**
- Sends `responseMimeType: "application/json"`
- Includes `responseJsonSchema` defining expected structure
- Schema enforces array of objects with `normalized` and `regex` properties

**Response Format:**
```json
{
  "normalizations": [
    {"normalized": "Amazon Marketplace", "regex": "AMZNMKTP.*"},
    {"normalized": "Tesco", "regex": "TESCO.*"}
  ]
}
```

**Benefits:**
- Guaranteed valid JSON syntax
- Type-safe responses (no parsing errors)
- Predictable structure for testing
- Better error messages on validation failure
- Regex patterns validated before storage

**Regex Safety:**
- All regex patterns from Gemini are validated before use
- Uses `~` delimiter for `preg_match()` (less likely to conflict than `/`)
- Invalid patterns are logged and discarded (set to null)
- Prompts explicitly tell Gemini to use PHP-compatible regex without delimiters
- Example validation: `@preg_match('~' . $pattern . '~i', '')` tests pattern validity

**Implementation:**
```php
$schema = [
    'type' => 'object',
    'properties' => [
        'normalizations' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'normalized' => ['type' => 'string'],
                    'regex' => ['type' => 'string'],
                ],
                'required' => ['normalized', 'regex'],
            ],
        ],
    ],
    'required' => ['normalizations'],
];

$response = $gemini->chat($systemPrompt, $userPrompt, $schema);

// Response is validated: invalid regex → null + logged warning
```

### Merchant Normalization
**Table:** `merchant_normalizations`

**Columns:**
- `raw_merchant` - Original merchant string (unique)
- `normalized_merchant` - Clean display name
- `regex_pattern` - Pattern to match variations (unique, nullable)
- `detection_method` - ai | regex | manual

**Matching Priority:**
1. Exact match on `raw_merchant`
2. Regex match on any `regex_pattern` (using `~` delimiter with case-insensitive flag)
3. AI normalization (creates new record)

**Regex Execution:**
- Patterns use `~` delimiter: `@preg_match('~' . $pattern . '~i', $merchant)`
- Case-insensitive matching (i flag)
- Error suppression (@) to handle any edge cases gracefully
- Invalid patterns during normalization are logged and discarded

**Example:**
```
raw_merchant: "AMZNMKTPLACE*ZD11O1PS4  AMAZON.CO.UK"
normalized_merchant: "Amazon Marketplace"
regex_pattern: "AMZNMKTP.*"
detection_method: "ai"
```

### Category Normalization
**Table:** `category_normalizations`

Same structure as merchant normalizations but for categories.

**Example:**
```
raw_category: "General Purchases-Online Purchases"
normalized_category: "Shopping"
regex_pattern: "General\\s+Purchases.*Online"
detection_method: "ai"
```

### Deduplication Strategy

**Problem:** AI may return identical regex patterns for multiple items in a batch.

**Solution - Three Protection Layers:**

1. **In-PHP Deduplication** (TransactionNormalizationService)
   - Tracks seen regex patterns within batch
   - Only inserts first occurrence with each pattern
   - Still associates all raw values with correct normalized name

2. **Upsert Logic**
   - Uses `upsert()` with `regex_pattern` as unique key
   - Handles race conditions between concurrent imports
   - Updates existing patterns instead of failing

3. **Database Constraints**
   - `UNIQUE` constraint on `regex_pattern` column
   - Prevents duplicates at database level
   - NULL patterns allowed (multiple raw entries with no pattern)

## Console Command

### Usage
```bash
php artisan import:csv {userId} {path} {--type=amex} {--async}
```

**Arguments:**
- `userId` - User ID who owns the transactions
- `path` - Path to CSV file (relative to storage disk)

**Options:**
- `--type=amex` - Import service type (default: amex)
- `--async` - Process asynchronously via queue

**Examples:**
```bash
# Synchronous import
php artisan import:csv 1 imports/statement.csv --type=amex

# Asynchronous import
php artisan import:csv 1 imports/statement.csv --type=amex --async
```

## Creating New Import Services

### Step 1: Create Service Class
```php
<?php

namespace App\Services\Import;

use App\Models\Provider;

class MyBankImportService extends AbstractImportService
{
    // Define payment patterns for this provider
    private const PAYMENT_PATTERNS = [
        'PAYMENT.*RECEIVED',
        'TRANSFER.*IN',
    ];

    protected function getType(): string
    {
        return 'mybank';
    }

    protected function getRequiredCSVHeaders(): array
    {
        return ['date', 'description', 'amount', 'reference'];
    }

    protected function getRowTransactionID(array $row): string
    {
        return trim($row['reference'], "'");
    }

    protected function getProviderId(): int
    {
        return Provider::where('code', 'mybank')->value('id');
    }

    protected function getAccountType(): string
    {
        return Provider::ACCOUNT_TYPE_DEBIT; // or CREDIT, INVESTMENT, CASH
    }

    protected function isPayment(array $row): bool
    {
        $description = $row['description'] ?? '';
        
        foreach (self::PAYMENT_PATTERNS as $pattern) {
            if (@preg_match('~' . $pattern . '~i', $description)) {
                return true;
            }
        }
        return false;
    }

    protected function formatRowForImport(array $row): array
    {
        return [
            'transaction_date' => $this->parseDate($row['date']),
            'description' => $row['description'],
            'payee' => $row['description'],
            'amount' => (float) $row['amount'],
            'currency' => $this->getCurrency(),
        ];
    }

    private function parseDate(string $date): string
    {
        // Implement date parsing logic
        return date('Y-m-d', strtotime($date));
    }
}
```

### Step 2: Add Provider to Database
Create a migration or seed to add your provider:
```php
DB::table('providers')->insert([
    'code' => 'mybank',
    'name' => 'My Bank',
    'type' => 'bank',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Step 3: Register in Console Command
Edit `app/Console/Commands/ImportCSV.php`:
```php
protected $services = [
    'amex' => AmericanExpressImportService::class,
    'mybank' => MyBankImportService::class, // Add your service
];
```

### Step 4: Test Your Service
Create tests in `tests/Feature/Console/` and `tests/Unit/Services/Import/`.

See [TESTING.md](TESTING.md) for CSV import testing patterns.

## Best Practices

### CSV Format
- First row must be headers
- Headers will be normalized (lowercase, spaces to underscores)
- All values are automatically trimmed
- Use fixture files for tests (`tests/fixtures/csv/`)

### Error Handling
- Invalid headers throw `InvalidHeadersException`
- Row count mismatch throws `InvalidRowException`
- All exceptions mark Import as 'failed'
- Failed imports log error message

### Performance
- 100 rows per chunk balances memory and AI API calls
- Normalization cache reduces AI calls on subsequent imports
- Regex patterns enable fuzzy matching without AI
- Upsert strategy prevents duplicate transactions

### Data Privacy
- Never commit real transaction data
- Use generic test data in fixtures
- Sanitize any logs or error messages
- Test with obfuscated merchant names

## Troubleshooting

### Import Stuck in 'processing' Status
- Check queue worker is running: `php artisan queue:work`
- Check for errors in `storage/logs/laravel.log`
- Verify file exists at specified path

### Duplicate Transactions
- Ensure `getRowTransactionID()` returns unique identifiers
- Check transaction_id format is consistent
- Verify upsert is using correct unique key

### Normalization Not Working
- Check GeminiService API key is configured
- Verify AI response format (text, newline-separated)
- Check DefaultCategory table has categories seeded
- Review normalization cache tables for existing patterns

### CSV Header Validation Fails
- Headers are case-insensitive and space/underscore normalized
- Check `getRequiredCSVHeaders()` matches actual CSV
- Use normalized header names (lowercase, underscores)
- Verify CSV has no hidden characters or BOM

## Related Files

**Core Services:**
- `app/Services/Import/AbstractImportService.php` - Base import service
- `app/Services/Import/AmericanExpressImportService.php` - Amex implementation
- `app/Services/TransactionNormalizationService.php` - Normalization logic
- `app/Services/GeminiService.php` - AI normalization client

**Models:**
- `app/Models/Import.php` - Import tracking
- `app/Models/Provider.php` - Provider information
- `app/Models/UserTransaction.php` - Transaction storage with provider/type fields
- `app/Models/MerchantNormalization.php` - Merchant cache
- `app/Models/CategoryNormalization.php` - Category cache

**Jobs:**
- `app/Jobs/ProcessCSVImport.php` - Async import job

**Commands:**
- `app/Console/Commands/ImportCSV.php` - CLI interface

**Migrations:**
- `database/migrations/2026_01_21_220000_create_normalization_cache_tables.php`
- `database/migrations/2026_01_23_000000_add_unique_constraint_to_normalization_regex_patterns.php`
- `database/migrations/2026_01_23_120000_create_providers_table.php`
- `database/migrations/2026_01_23_120001_add_provider_and_types_to_user_transactions.php`

**Tests:**
- `tests/Feature/Console/ImportCSVTest.php` - Command tests
- `tests/Feature/ImportTransactionsTest.php` - Integration tests
- `tests/Unit/Services/Import/AmericanExpressImportServiceTest.php` - Unit tests
- `tests/Unit/Services/TransactionNormalizationServiceTest.php` - Normalization tests
