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
