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
- Each import creates an `Import` record with status tracking (processing/completed/failed)
- Transaction normalization flow:
	1. Extract unique merchants/categories from chunk
	2. Check normalization cache (exact match → regex match)
	3. Batch remaining unknowns to AI for normalization
	4. Cache results (raw + normalized + regex pattern)
	5. Upsert transactions with normalized data
- Console command: `php artisan import:csv {userId} {path} {--type=amex}`
- Import services must:
	- Define required CSV headers via `getRequiredCSVHeaders()`
	- Extract transaction ID via `getRowTransactionID()`
	- Format row data via `formatRowForImport()`
	- Optionally extract category via `extractCategory()` (implement `HasCategory`)
- Storage disk defaults to 'local' (override with `setDisk()`)
- Chunk size: 100 rows per batch

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
