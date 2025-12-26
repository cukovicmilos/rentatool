# CHECKPOINT 5: Javni katalog

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M3-Katalog

## Opis

Provera da javni katalog (frontend) radi ispravno.

## Testirati

### Početna stranica
URL: `https://labubush.duckdns.org/rentatool/`

- [ ] Prikazuju se svi dostupni alati u grid-u
- [ ] Svaki alat ima sliku, naziv, cenu, vikend cenu
- [ ] Klik na alat vodi na detalj stranicu
- [ ] Sidebar prikazuje kategorije sa brojem alata

### Stranica kategorije
URL: `https://labubush.duckdns.org/rentatool/kategorija/busilice`

- [ ] Prikazuje se naziv kategorije
- [ ] Breadcrumbs navigacija radi
- [ ] Prikazuju se samo alati iz te kategorije
- [ ] Sidebar označava aktivnu kategoriju

### Detalj alata
URL: Klikni na bilo koji alat

- [ ] Prikazuje se galerija slika (ako ima više slika, thumbnails rade)
- [ ] Prikazuju se cene (redovna, vikend, depozit)
- [ ] Prikazuju se specifikacije (ako postoje)
- [ ] Kalendar za izbor datuma radi
- [ ] Izbor datuma prikazuje kalkulaciju cene
- [ ] Vikend dani se pravilno računaju (+10%)
- [ ] Popust za 7+ dana se prikazuje (-10%)
- [ ] Dugme "Dodaj u korpu" dodaje alat u korpu
- [ ] Broj stavki u korpi (header) se ažurira

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, označiti kao Done i nastaviti sa M4-Korpa.
