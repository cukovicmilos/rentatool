# Kreiranje SQLite baze podataka

- **Status**: To Do
- **Labels**: database
- **Milestone**: M1-Osnova

## Opis

Dizajnirati i implementirati SQLite šemu baze podataka.

## Tabele

- [ ] `settings` - podešavanja sajta (ime, kontakt, dostava cene, vikend procenat)
- [ ] `admins` - admin korisnici (username, password_hash, created_at)
- [ ] `categories` - kategorije (id, parent_id, name, slug, sort_order, active)
- [ ] `tools` - alati (id, name, slug, description, price_24h, deposit, status, created_at)
- [ ] `tool_images` - slike alata (id, tool_id, filename, sort_order, is_primary)
- [ ] `tool_specifications` - specifikacije (id, tool_id, spec_name, spec_value)
- [ ] `tool_categories` - many-to-many veza alat-kategorija
- [ ] `blocked_dates` - blokirani datumi (id, date, reason)
- [ ] `reservations` - rezervacije (id, reservation_number, status, customer_*, dates, totals, delivery_option, created_at)
- [ ] `reservation_items` - stavke rezervacije (id, reservation_id, tool_id, price_per_day, days, subtotal)
- [ ] `pages` - statičke stranice (id, slug, title, content, active)

## Zadaci

- [ ] Kreirati schema.sql fajl
- [ ] Kreirati migration/install skriptu
- [ ] Dodati seed podatke za admina i osnovne kategorije
