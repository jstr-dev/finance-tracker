# CSV Import System

## Core Concepts

Import services extend `AbstractImportService` and handle CSV file processing, transaction normalization, and provider/type tracking.

**Key Features:**
- 100-row chunk processing
- Auto-trim CSV values
- Sync/async modes via `startImport($user, $path, $async)`
- AI-powered merchant/category normalization with structured JSON outputs
- Provider tracking (AMEX, Monzo, Trading212)
- Account types: credit, debit, investment, cash
- Transaction types: purchase, payment (regex-detected per provider)

## Required Implementation

## Required Implementation

```php
abstract protected function getType(): string;
abstract protected function getRequiredCSVHeaders(): array;
abstract protected function getRowTransactionID(array $row): string;
abstract protected function formatRowForImport(array $row): array;
abstract protected function getProviderId(): int;
abstract protected function getAccountType(): string;

// Optional: detect payment transactions
protected function isPayment(array $row): bool { return false; }

// Optional: extract category if HasCategory interface implemented
public function extractCategory(array $row): ?string;
```

## Import Flow

1. Create `Import` record (processing status)
2. Validate CSV headers
3. Process in 100-row chunks:
   - Extract unique merchants/categories
   - Check cache (exact → regex with `~` delimiter)
   - Send unknowns to AI (JSON schema, validated regex)
   - Deduplicate patterns, upsert normalizations
   - Add provider_id, account_type, transaction_type
   - Detect payments via provider patterns
   - Upsert transactions by transaction_id
4. Mark complete/failed

## Provider System

**Providers table:** id, code, name, type (credit_card/bank/investment/crypto)

**Seeded:** amex, monzo, trading212

**Account types:** credit (debt), debit (real money), investment, cash

**Transaction types:** purchase (default), payment (detected by patterns like `PAYMENT.*THANK YOU`, `DIRECT DEBIT`, `AUTOPAY`)

Payment detection example:
```php
protected function isPayment(array $row): bool
{
    $text = ($row['description'] ?? '') . ' ' . ($row['payee'] ?? '');
    foreach (self::PAYMENT_PATTERNS as $pattern) {
        if (@preg_match('~' . $pattern . '~i', $text)) return true;
    }
    return false;
}
```

## Normalization

**GeminiService:** Uses JSON schema for type-safe responses:
```json
{"normalizations": [{"normalized": "Name", "regex": "PATTERN"}, ...]}
```

**Validation:** Regex patterns tested with `@preg_match('~' . $pattern . '~i', '')` before storage. Invalid → null + logged.

**Tables:** merchant_normalizations, category_normalizations
- Columns: raw, normalized, regex_pattern (unique), detection_method (ai/regex/manual)
- Matching: exact → regex → AI
- Deduplication: in-PHP + upsert + DB unique constraint

## Adding New Provider

1. **Create service:** Extend AbstractImportService, implement required methods
2. **Seed provider:** Insert into providers table with code/name/type
3. **Register:** Add to ImportCSV command services array
4. **Test:** See TESTING.md

## Console Command

```bash
php artisan import:csv {userId} {path} {--type=amex} {--async}
```

## Key Files

- AbstractImportService: Base class
- AmericanExpressImportService: AMEX implementation
- TransactionNormalizationService: Normalization logic
- Provider model: Constants for types
- UserTransaction model: Scopes/helpers for types
- Migrations: 2026_01_23_120000_create_providers_table, 2026_01_23_120001_add_provider_and_types_to_user_transactions
