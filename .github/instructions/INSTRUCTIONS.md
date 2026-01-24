# Project Instructions

## Business goals
- The app is a Finance Tracker that aggregates user accounts and imports to build a unified view of their finances.
- Primary account types to support:
	- Investments
	- Bank accounts
	- Crypto exchanges
	- Credit cards

## Data acquisition
- Support two connection methods:
	- Direct OAuth/API connections for providers that support it.
	- User-driven imports (CSV or similar) when APIs are unavailable.
- Each connection/import should map transactions to a normalized schema (date, amount, merchant/description, category, type, currency, and unique transaction id).

### CSV Import Architecture
- Import services extend `AbstractImportService`
- Service owns import lifecycle via `startImport()` method
- Each import creates an `Import` record with status tracking (processing/completed/failed)
- Supports sync and async modes:
	- Sync: `$service->startImport($user, $path, false)` - blocks until complete
	- Async: `$service->startImport($user, $path, true)` - dispatches `ProcessCSVImport` job
- **Provider System:**
	- Providers table stores provider info (AMEX, Monzo, Trading212, etc.)
	- Each transaction linked to provider via `provider_id`
	- Account types: `credit`, `debit`, `investment`, `cash`
	- Transaction types: `purchase` (default), `payment` (detected per-provider)
	- Services must implement `getProviderId()` and `getAccountType()`
	- Optional `isPayment(array $row): bool` for payment detection
- Transaction normalization flow:
	1. Extract unique merchants/categories from chunk
	2. Check normalization cache (exact match → regex match with ~ delimiter)
	3. Batch remaining unknowns to AI for normalization
	4. **Validate regex patterns** - test each pattern, discard invalid ones
	5. **Deduplicate regex patterns** before storing (prevent duplicates)
	6. Upsert normalizations by regex_pattern (handle race conditions)
	7. Cache results (raw + normalized + regex pattern)
	8. Add provider metadata (provider_id, account_type, transaction_type)
	9. Detect payments using provider-specific patterns
	10. Upsert transactions with normalized data
- Console command: `php artisan import:csv {userId} {path} {--type=amex} {--async}`
- Import services must:
	- Implement `getType()` - returns import type identifier
	- Define required CSV headers via `getRequiredCSVHeaders()`
	- Extract transaction ID via `getRowTransactionID()`
	- Format row data via `formatRowForImport()`
	- Implement `getProviderId()` - returns provider ID from database
	- Implement `getAccountType()` - returns account type constant
	- Optionally implement `isPayment()` - detect payment transactions
	- Optionally extract category via `extractCategory()` (implement `HasCategory`)
- Storage disk defaults to 'local' (override with `setDisk()`)
- Chunk size: 100 rows per batch
- **All CSV values are automatically trimmed** before processing
- Normalization tables have unique constraints on regex_pattern columns

## Core insights to deliver
- Dashboard must visualize:
	- Net worth
	- Spending totals
	- Debt totals
	- Spending per category
- Calculations should be consistent across sources (e.g., imports and API connections should behave the same).

## Product expectations
- Prioritize clarity and accuracy of totals and charts over cosmetic UI changes.
- New data sources must not break existing dashboards or summaries.
- When adding a provider, include sensible defaults for categories and currency normalization.

## Guardrails
- Never hardcode secrets. Use .env and config files (config/services.php).
- Do not modify vendor/, node_modules/, or public/build/.
