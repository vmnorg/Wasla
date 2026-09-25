# Spec: WASLA (Agri-Logistics Platform for Souss-Massa)

## Objective
WASLA is a clean, native PHP and MySQL agri-logistics platform designed for the Souss-Massa region in Morocco. It empowers smallholder farmers to aggregate agricultural offers through voice or text in Moroccan Darija. These inputs are processed via the Gemini 1.5 Flash API (Darija NLU) into structured JSON, matched to local communes, grouped into transport-optimized "lots" (target 5-10 tons to minimize empty return trips and reduce CO2), and visualized on an interactive Leaflet.js dashboard with intelligent routing.

### Key Use Cases
1. **Darija NLU Processing:** Farmer speaks/writes "3andi 2 toun d l-khizou mzyan f Massa" (I have 2 tons of good carrots in Massa) -> processed to JSON.
2. **Offer Collection:** Structured offers are stored with commune-linked coordinates.
3. **Logistics Pooling (Aggregation):** Grouping offers from neighboring communes into 5-10 ton batches (Lots) to fill single logistics trucks.
4. **Logistics Dashboard:** Map-based dashboard for transport operators displaying active offers, consolidated lots, and optimized routing paths.

---

## Tech Stack
- **Backend:** Native PHP 8.1+ (Clean Architecture, PDO, strict types, no external heavy framework).
- **Database:** MySQL 8.0+ (InnoDB, spatial indexes or decimal coordinate storage, proper foreign keys).
- **Frontend:** HTML5, CSS (Tailwind CSS via CDN), Javascript (Vanilla ES6 with modern modular architecture, or simple React), Leaflet.js + Leaflet Routing Machine + OSRM for map-based logistics routing.
- **External APIs:**
  - Gemini 1.5 Flash API (for Darija NLP processing).
  - OSRM (Open Source Routing Machine) public API for routing.

---

## Commands
- **Database Initialization:** Run the schema on MySQL.
- **PHP Dev Server:** `php -S localhost:8000 -t public/` (or running from root if root-based config).
- **Linting/Formatting:** `composer exec phpcbf` / `composer exec phpcs` (if composer is configured, otherwise native PHP checks).

---

## Project Structure
We adopt a clean, maintainable folder structure that separates core configuration, public-facing assets, api endpoints, and business logic:

```
wasla/
├── config/
│   └── db.php               # PDO database connection setup
├── api/
│   ├── process_nlu.php       # Processes Darija input with Gemini 1.5 Flash
│   ├── get_offers.php        # Retrieves active agri-offers
│   └── aggregate_lots.php    # Business logic for grouping offers into logical lots
├── public/
│   ├── index.php             # Core UI / Logistics Dashboard (HTML/Tailwind/Leaflet)
│   ├── css/
│   │   └── app.css           # Custom styles (if any)
│   └── js/
│       └── app.js            # Leaflet map, dashboard reactive updates, routing logic
├── database/
│   └── schema.sql            # MySQL database schema + seed data
├── .env.example              # Example environment variables
└── README.md                 # Project overview and installation guide
```

---

## Code Style
### PHP Conventions
- Strict typing enabled (`declare(strict_types=1);` at the top of each file).
- Use `PDO` with prepared statements for all database interactions to prevent SQL injection.
- JSON API responses must set `header('Content-Type: application/json; charset=utf-8');` and return clean JSON status structures.
- PascalCase for Class names, camelCase for variables/methods, snake_case for database fields.

Example:
```php
<?php
declare(strict_types=1);

namespace Wasla\Config;

use PDO;
use PDOException;

class Database {
    private ?PDO $conn = null;

    public function getConnection(): PDO {
        // ...
    }
}
```

### Database Conventions
- Explicit foreign keys with constraints.
- Coordinates represented as `DECIMAL(10, 8)` for latitude and `DECIMAL(11, 8)` for longitude (accurate to within ~1mm, ideal for mapping).
- Table and column names in `snake_case` plural (e.g., `communes`, `offers`, `lots`, `lot_offers`).

---

## Testing Strategy
- **Manual API Testing:** JSON endpoints can be verified via Postman, curl, or browser-based tools.
- **DB Connection Verification:** Simple script validation.
- **NLU Testing:** Mocking Gemini responses to verify JSON payload parsing and fallback structures.

---

## Boundaries
- **Always do:**
  - Secure DB queries using prepared statements.
  - Return clean JSON errors on API failures (e.g., `{"success": false, "error": "Reason"}`).
  - Document coordinates with proper Souss-Massa region bounds.
- **Ask first:**
  - Changing MySQL schema structure after Step 1.
  - Introducing heavy PHP frameworks (like Laravel/Symfony) since native/clean is requested.
- **Never do:**
  - Hardcode sensitive API keys (e.g., Gemini API keys) or DB credentials in source code.
  - Commit actual `.env` file to git.

---

## Success Criteria for Step 1
1. **Directory Layout:** Correct creation of `/config`, `/api`, `/public`, and `/database` folders.
2. **Database Schema:** `schema.sql` written with 4 correct tables (`communes`, `offers`, `lots`, `lot_offers`) with correct spatial coordinates for 6 seeded Souss-Massa communes.
3. **PDO Connection:** `/config/db.php` is complete, supports strict typing, reads from environment variables (with fallback for easy local dev), and successfully initializes a PDO connection with error-handling.
4. **API Placeholders:** Basic structured skeleton endpoints for `process_nlu.php`, `get_offers.php`, and `aggregate_lots.php` that return standard JSON envelopes.

## Open Questions
- What are the precise credentials or environment structure for DB connection locally?
  - *Recommendation:* Create a standard `.env.example` file and have `db.php` check standard environment variables or fallback to `localhost`, root, and blank/root password for simple local developer onboarding.
