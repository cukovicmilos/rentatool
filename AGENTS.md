# Agent Notes - Rent a Tool

## Backlog Workflow

This project uses Backlog.md MCP for all task and project management activities.

- If your client supports MCP resources, read `backlog://workflow/overview` to understand when and how to use Backlog for this project.
- If your client only supports tools or the above request fails, call `backlog.get_workflow_overview()` tool to load the tool-oriented overview.
- Read the overview **before creating tasks**, or when you're unsure whether to track work.

## Environment

- **This is the production environment.** The workspace root (`/var/www/rentatool`) is the live site.
- The SQLite database (`database/rentatool.db`) is the production database.
- Changes made here take effect immediately on the live site.
- SQLite baza je u WAL modu (izbjegava lock contention).
- Baza i `.git` folder su u vlasništvu `www-data:www-data`.

## Deployment

- Production deploys are triggered by pushing `origin/main` to GitHub.
- The GitHub webhook calls `api/deploy.php`, which:
  1. `git fetch origin && git reset --hard origin/main` — povlači najnoviji kod
  2. `sudo systemctl reload php8.4-fpm` — reload PHP-FPM (čisti opcache)
  3. `sleep 2` — čeka da se PHP stabilizuje
  4. `bash scripts/smoke-test.sh` — pokreće smoke test
  5. Ako smoke test padne → Telegram notifikacija "Deploy failed!"
- **Do not commit, push, or run git mutations unless explicitly requested.**

## Database Migrations

- Migrations live in `database/migrations/`.
- The runner is `database/migrate.php`.
- When a schema change is added, run it directly on production:

  ```bash
  php database/migrate.php
  ```

- `database/schema.sql` and `database/install.php` should be kept in sync with migrations.

## Smoke Test (`scripts/smoke-test.sh`)

- After deploy, the smoke test runs automatically.
- It simulates the complete checkout flow:
  - Homepage (HTTP 200)
  - Fetch first available tool from DB
  - Add to cart (`POST /api/cart`)
  - Verify cart
  - Extract CSRF token from checkout page
  - Submit checkout form (name: Test Korisnik, email: test@rentatool.in.rs, etc.)
  - Verify thank you page and reservation code
  - Verify reservation in SQLite database
  - Clean up test reservation at the end
- Period: +5/+7 days from today (within `MAX_ADVANCE_DAYS = 30`).
- Requirements: `curl`, `sqlite3`, `python3`, `jq`.
- If you modify the smoke test, keep the test dates within `MAX_ADVANCE_DAYS` (30 days).

## Key Files

- `api/deploy.php` — GitHub webhook endpoint (triggers deploy)
- `scripts/post-receive-hook.sh` — fallback hook for direct git push to server
- `scripts/smoke-test.sh` — smoke test
- `api/cart.php` — cart API (has `MAX_ADVANCE_DAYS` validation)
- `pages/checkout.php` — checkout page

## Components

- HTML/CSS/JS used on more than one page goes into `templates/components/` as a single include file (e.g., `service-modal.php`).
- Pages include it with `<?php include TEMPLATES_PATH . '/components/...' ?>`.
- This applies to all new components; existing duplicated code should be refactored into components when encountered.
