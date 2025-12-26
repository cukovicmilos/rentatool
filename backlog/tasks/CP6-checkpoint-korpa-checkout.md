# CHECKPOINT 6: Korpa i Checkout

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M4-Korpa-Checkout

## Opis

Provera da korpa i checkout proces rade ispravno.

## Testirati

### Dodavanje u korpu
- [ ] Idi na detalj bilo kog alata
- [ ] Izaberi datume (npr. od sutra do prekosutra)
- [ ] Klikni "Dodaj u korpu"
- [ ] Broj u korpi (header) se ažurira

### Stranica korpe
URL: `/rentatool/korpa`

- [ ] Prikazuje se lista dodanih alata
- [ ] Prikazuju se datumi, broj dana, cene
- [ ] Vikend dani imaju uvećanu cenu (+10%)
- [ ] Popust za 7+ dana se prikazuje (-10%)
- [ ] Dugme "Ukloni" briše stavku
- [ ] Ukupna cena je ispravna

### Checkout
URL: `/rentatool/checkout`

- [ ] Forma za podatke kupca se prikazuje
- [ ] Obavezna polja: ime, email, telefon
- [ ] Opcije dostave se prikazuju (pickup/delivery/roundtrip)
- [ ] Cena dostave se ažurira
- [ ] Pregled narudžbe prikazuje sve stavke
- [ ] Ukupna cena uključuje dostavu

### Završetak narudžbe
- [ ] Popuni formu sa validnim podacima
- [ ] Klikni "Potvrdi rezervaciju"
- [ ] Redirectuje na Thank You stranicu
- [ ] Prikazuje se broj rezervacije
- [ ] Dugme "Štampaj" otvara print dijalog
- [ ] Korpa je ispražnjena

### Admin provera
- [ ] Idi na `/rentatool/admin/rezervacije`
- [ ] Nova rezervacija se pojavljuje u listi
- [ ] Status je "pending"
- [ ] Klik na "Detalji" prikazuje sve informacije

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, označiti kao Done.
