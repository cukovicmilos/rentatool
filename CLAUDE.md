
<!-- BACKLOG.MD MCP GUIDELINES START -->

<CRITICAL_INSTRUCTION>

## BACKLOG WORKFLOW INSTRUCTIONS

This project uses Backlog.md MCP for all task and project management activities.

**CRITICAL GUIDANCE**

- If your client supports MCP resources, read `backlog://workflow/overview` to understand when and how to use Backlog for this project.
- If your client only supports tools or the above request fails, call `backlog.get_workflow_overview()` tool to load the tool-oriented overview (it lists the matching guide tools).

- **First time working here?** Read the overview resource IMMEDIATELY to learn the workflow
- **Already familiar?** You should have the overview cached ("## Backlog.md Overview (MCP)")
- **When to read it**: BEFORE creating tasks, or when you're unsure whether to track work

These guides cover:
- Decision framework for when to create tasks
- Search-first workflow to avoid duplicates
- Links to detailed guides for task creation, execution, and completion
- MCP tools reference

You MUST read the overview resource to understand the complete workflow. The information is NOT summarized here.

</CRITICAL_INSTRUCTION>

<!-- BACKLOG.MD MCP GUIDELINES END -->

## Deploy Flow

Git push na `origin/main` (GitHub) → GitHub webhook → `api/deploy.php`:
1. `git fetch origin && git reset --hard origin/main` - povlači najnoviji kod
2. `sudo systemctl reload php8.4-fpm` - reload PHP-FPM (čisti opcache)
3. `sleep 2` - čeka da se PHP stabilizuje
4. `bash scripts/smoke-test.sh` - pokreće smoke test
5. Ako smoke test padne → Telegram notifikacija "Deploy failed!"

## Smoke Test (`scripts/smoke-test.sh`)

Simulira kompletan checkout flow:
- Homepage (HTTP 200)
- Dohvatanje prvog dostupnog alata iz baze
- Dodavanje u korpu (`POST /api/cart`)
- Provera korpe
- Ekstrakcija CSRF tokena sa checkout stranice
- Slanje checkout forme (ime: Test Korisnik, email: test@rentatool.in.rs, itd.)
- Provera thank you stranice i reservation koda
- Verifikacija rezervacije u SQLite bazi
- Čišćenje test rezervacije na kraju

Period: +5/+7 dana od danas (unutar MAX_ADVANCE_DAYS = 30).
Zahtevi: `curl`, `sqlite3`, `python3`, `jq`.

## Ključni fajlovi

- `api/deploy.php` - GitHub webhook endpoint (trigera deploy)
- `scripts/post-receive-hook.sh` - rezervni hook za direktan git push na server
- `scripts/smoke-test.sh` - smoke test
- `api/cart.php` - cart API (ima MAX_ADVANCE_DAYS validaciju)
- `pages/checkout.php` - checkout stranica

## Poznato

- SQLite baza je u WAL modu (izbjegava lock contention)
- Baza: `database/rentatool.db`, vlasnik `www-data:www-data`
- `.git` folder: vlasnik `www-data:www-data`
- Ako se mijenja smoke test, moraju se ažurirati i datumi (držati unutar 30 dana)
