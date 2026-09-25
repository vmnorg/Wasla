# Implementation Plan: WASLA - Step 1

This plan maps out the initial database design, folder structure, configuration setup, and API skeleton implementation for the WASLA agri-logistics platform.

## Major Components & Dependencies
1. **Directories & Env Configuration (`.env.example`):** Foundation for standard environment-based configurations.
2. **Database Schema (`database/schema.sql`):** Core data design. Seeds the 6 Souss-Massa communes with realistic coordinate mapping.
3. **PDO Connection Class (`config/db.php`):** The driver to connect PHP with MySQL using PDO safely.
4. **API Skeletons (`api/`):** The operational layer that will handle Darija NLU processing, offers retrieval, and lot aggregation.
5. **UI Shell (`public/index.php`):** Entry-point containing the layout for the future Leaflet.js map-based dashboard.

---

## Technical Approach & Sizing
- **Native PHP 8.1+:** Zero external routing packages or DB query builders. Keep it lightweight, highly performant, and maintainable.
- **Strict Data Types:** All PHP files will enforce `declare(strict_types=1);` to prevent unexpected runtime conversions.
- **Clean JSON APIs:** Each API will enforce standard response JSON formats with appropriate headers.
- **Secure SQL:** Prepared statements only. No raw values interpolated into SQL strings.

---

## Risks & Mitigation
- **Risk:** Missing or incorrect database credentials on the local host causes immediate crashes.
  - *Mitigation:* The PDO connection file will read from `.env` (via standard PHP methods) but fallback to standard defaults (localhost, root, blank password) for easy local development.
- **Risk:** Out of bounds or incorrect coordinates for the 6 Souss-Massa communes.
  - *Mitigation:* Pre-verify the exact coordinates in the schema seed data.

---

## Verification Checkpoints
- **Phase 1 Checkpoint:** Directories exist and database schema is valid SQL.
- **Phase 2 Checkpoint:** `config/db.php` parses and connects cleanly.
- **Phase 3 Checkpoint:** API endpoints return valid JSON response headers and standard structures.
