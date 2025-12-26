# Rent a Tool - Plan Projekta

## O projektu

Servis za iznajmljivanje gradjevinske opreme u Subotici i okolini.

## Tech Stack

- PHP 8.x
- SQLite
- jQuery
- Vanilla CSS (Craigslist stil)

## Milestone-i i Checkpoint-i

### M1 - Osnova
Bazna infrastruktura projekta.
- Struktura foldera
- SQLite baza
- Template sistem
- CSS dizajn

#### CHECKPOINT 1: Osnova radi
Proveriti:
- [ ] Sajt se otvara na BASE_URL bez gresaka
- [ ] Baza je kreirana i moze se citati/pisati
- [ ] Layout se renderuje (header, sidebar, footer)
- [ ] CSS stilovi se ucitavaju (zuta dugmad, crno-beli dizajn)
- [ ] Responsive na mobilnom

---

### M2 - Admin Panel
CRUD operacije za upravljanje sadrzajem.
- Autentifikacija
- Kategorije
- Alati (sa slikama i specifikacijama)
- Blokirani datumi
- Staticke stranice

#### CHECKPOINT 2: Admin login
Proveriti:
- [ ] Admin login stranica radi
- [ ] Login sa ispravnim kredencijalima uspeva
- [ ] Login sa pogresnim kredencijalima prikazuje gresku
- [ ] Logout radi
- [ ] Zasticene stranice nisu dostupne bez logina

#### CHECKPOINT 3: Admin CRUD kategorije
Proveriti:
- [ ] Lista kategorija se prikazuje
- [ ] Dodavanje nove kategorije radi
- [ ] Izmena kategorije radi
- [ ] Brisanje prazne kategorije radi
- [ ] Podkategorije se ispravno prikazuju

#### CHECKPOINT 4: Admin CRUD alati
Proveriti:
- [ ] Lista alata se prikazuje
- [ ] Dodavanje alata sa svim poljima radi
- [ ] Upload slika radi (vise slika)
- [ ] Slike se kompresuju/resizuju
- [ ] Specifikacije se dodaju dinamicki
- [ ] Izmena alata radi
- [ ] Dodeljivanje kategorija radi

---

### M3 - Katalog
Javni prikaz alata.
- Pocetna stranica
- Stranica kategorije
- Detalj alata
- Kalendar dostupnosti

#### CHECKPOINT 5: Javni katalog
Proveriti:
- [ ] Pocetna prikazuje sve dostupne alate
- [ ] Sidebar kategorije su klikabilne
- [ ] Stranica kategorije filtrira alate ispravno
- [ ] Detalj alata prikazuje sve informacije
- [ ] Galerija slika radi
- [ ] Kalendar prikazuje dostupnost
- [ ] Vikend cena se ispravno prikazuje (+10%)
- [ ] Lazy loading slika radi

---

### M4 - Korpa i Checkout
Proces narudzbe.
- Korpa (session-based)
- Checkout forma
- Cenovnik logika
- Thank you stranica sa PDF

#### CHECKPOINT 6: Korpa
Proveriti:
- [ ] Dodavanje alata u korpu radi
- [ ] Korpa cuva odabrane datume
- [ ] Vise alata moze biti u korpi
- [ ] Uklanjanje iz korpe radi
- [ ] Cena se ispravno racuna (vikend +10%, 7+ dana -10%)

#### CHECKPOINT 7: Checkout i potvrda
Proveriti:
- [ ] Checkout forma validira obavezna polja
- [ ] Opcije dostave menjaju cenu (0/10/15 EUR)
- [ ] Rezervacija se cuva u bazi
- [ ] Thank you stranica prikazuje detalje
- [ ] Print dugme otvara print dijalog
- [ ] PDF download radi

---

### M5 - Rezervacije
Upravljanje rezervacijama.
- Admin pregled
- Otkazivanje od strane korisnika

#### CHECKPOINT 8: Upravljanje rezervacijama
Proveriti:
- [ ] Admin vidi listu rezervacija
- [ ] Filtriranje po statusu radi
- [ ] Promena statusa rezervacije radi
- [ ] Korisnik moze otkazati (ako je 2+ dana pre)
- [ ] Otkazivanje oslobadja datume

---

### M6 - Frontend Polish
Zavrsni sloj.
- Staticke stranice
- SEO optimizacija

#### CHECKPOINT 9: Staticke i SEO ✅
Proveriti:
- [x] Sve staticke stranice se prikazuju
- [x] Meta tagovi su ispravni
- [x] Sitemap.xml se generise
- [x] Robots.txt postoji

---

### M7 - Integracije
Dodatne funkcionalnosti.
- Telegram notifikacije

#### CHECKPOINT 10: Telegram (finalni) ✅
Proveriti:
- [x] Nova rezervacija salje Telegram poruku
- [x] Poruka sadrzi sve bitne informacije

## Cene i pravila

- Cena: EUR po 24h
- Vikend: +10%
- 7+ dana: -10% popust
- Max dana: 10
- Max unapred: 30 dana
- Otkazivanje: min 2 dana pre

## Dostava (Subotica i okolina)

- Licno preuzimanje: 0 EUR
- Samo dostava: 10 EUR
- Dostava + preuzimanje: 15 EUR

## Kategorije (inicijalne)

1. Busilice
2. Brusilice
3. Slicerice
4. Rucni alati
5. Cirkulari
6. Makaze
7. Smirgla/Poliranje

## Dizajn

Craigslist stil:
- Bela pozadina
- Crni tekst
- Zuti akcent (#FFD700)
- Bez velikih bannera
- Minimalisticki, funkcionalan
