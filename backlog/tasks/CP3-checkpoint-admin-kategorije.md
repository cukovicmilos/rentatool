# CHECKPOINT 3: Admin CRUD kategorije

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M2-Admin

## Opis

Provera da CRUD operacije za kategorije rade ispravno.

## Testirati

URL: `http://localhost/rentatool/admin/kategorije` (mora biti ulogovan)

### Lista
- [ ] Lista kategorija se prikazuje
- [ ] Prikazuje se 7 inicijalnih kategorija (Bušilice, Brusilice, itd.)
- [ ] Svaka kategorija ima dugmad "Izmeni" i "Obriši"

### Dodavanje
- [ ] Klik na "+ Dodaj kategoriju" otvara formu
- [ ] Popuniti naziv i sačuvati - kategorija se pojavljuje u listi
- [ ] Slug se automatski generiše iz naziva

### Izmena
- [ ] Klik na "Izmeni" otvara formu sa postojećim podacima
- [ ] Promena naziva i čuvanje - izmene su vidljive u listi

### Brisanje
- [ ] Klik na "Obriši" prikazuje potvrdu
- [ ] Potvrda briše kategoriju iz liste
- [ ] Kategorija sa alatima se NE može obrisati (prikazuje grešku)

### Podkategorije
- [ ] Može se izabrati parent kategorija pri kreiranju
- [ ] Podkategorije se prikazuju uvučeno u listi

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, označiti kao Done i nastaviti sa CHECKPOINT 4.
