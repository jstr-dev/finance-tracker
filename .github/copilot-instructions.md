# Copilot Instructions

**Additional Instructions:** Read all files matching `.github/instructions/**/*.md`

## Project summary
- Laravel 12 backend with Inertia.js + React 19 + TypeScript frontend.
- Vite + Tailwind CSS for builds and styling.
- Inertia pages live in resources/js/pages (lowercase file names) and map to server routes via `Inertia::render('page-name')`.

## Key directories
- app/Http: controllers, middleware, requests.
- app/Services: business logic and integrations.
- app/Jobs: async work.
- app/Models: Eloquent models.
- resources/js: React app (components, layouts, hooks, pages).
- resources/css: Tailwind entrypoint.
- routes: route definitions split by area.
- database/migrations: schema changes.
- tests: Pest + PHPUnit.

## Coding conventions
- Follow Laravel conventions and PSR-12 in PHP.
- Use strict, typed TypeScript; prefer functional React components.
- Use `@/` alias for imports from resources/js.
- Keep page names and file names aligned (e.g., `Inertia::render('dashboard')` → resources/js/pages/dashboard.tsx).
- Keep changes focused; avoid reformatting unrelated code.

## Do not edit
- vendor/, node_modules/, public/build/.

## Common commands
- Backend dev: `composer run dev` (runs server, queue, logs, and Vite).
- Frontend dev: `npm run dev`.
- Build: `npm run build` (or `npm run build:ssr`).
- Lint: `npm run lint`.
- Types: `npm run types`.
- Format: `npm run format`.
- Tests: `php artisan test` (or `./vendor/bin/pest`).
