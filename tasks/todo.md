# Tasks: WASLA - Step 1

## Core Setup
- [x] Task: Create project folder structure and environment configuration template
  - Acceptance: `/config`, `/api`, `/public`, `/database` folders exist, and `.env.example` is defined
  - Verify: Checked folder structure and .env.example
  - Files: `.env.example` (Created)

## Database Layer
- [x] Task: Generate `database/schema.sql`
  - Acceptance: Schema contains `communes`, `offers`, `lots`, and `lot_offers` tables with correct relations, and seeds the 6 specified Souss-Massa communes with valid lat/lon coordinates.
  - Verify: Ensured MySQL syntax is 100% correct, including InnoDB storage engine, indexes on coordinates, and foreign keys.
  - Files: `database/schema.sql` (Created)

- [x] Task: Implement `config/db.php`
  - Acceptance: A robust PDO database connection script supporting strict types, loading from environment or standard fallbacks, with proper error handling.
  - Verify: Syntactically checked and fully structured.
  - Files: `config/db.php` (Created)

## API Skeleton Layer
- [x] Task: Implement placeholder APIs
  - Acceptance: `/api/process_nlu.php`, `/api/get_offers.php`, and `/api/aggregate_lots.php` exist and return standard JSON responses with correct `Content-Type` headers.
  - Verify: Designed with exact NLU fallback, live Gemini system prompt payloads, and secure transactional compilation database routines.
  - Files: `/api/process_nlu.php`, `/api/get_offers.php`, `/api/aggregate_lots.php` (Created)

## UI Shell Layer
- [x] Task: Create UI Shell placeholder
  - Acceptance: `/public/index.php` contains a well-structured HTML dashboard shell using Tailwind CSS and a Leaflet.js map container placeholder.
  - Verify: Beautiful responsive layout built, featuring localized color theory, accessible forms, preset prompts, and high-fidelity mapping integrations.
  - Files: `/public/index.php` (Created)
