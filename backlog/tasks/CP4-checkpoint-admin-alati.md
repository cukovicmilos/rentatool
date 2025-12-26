# CHECKPOINT 4: Admin CRUD alati

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M2-Admin

## Opis

Provera da CRUD operacije za alate rade ispravno.

## Testirati

URL: `http://localhost/rentatool/admin/alati` (mora biti ulogovan)

### Lista
- [ ] Lista alata se prikazuje (prazna na početku)
- [ ] Filter po kategoriji radi
- [ ] Filter po statusu radi

### Dodavanje alata
- [ ] Klik na "+ Dodaj alat" otvara formu
- [ ] Popuniti sva polja:
  - Naziv: "Bosch bušilica GSB 13 RE"
  - Kratak opis: "Profesionalna udarna bušilica"
  - Detaljan opis: (bilo koji tekst)
  - Cena 24h: 15
  - Depozit: 50
  - Status: Dostupan
  - Kategorije: označiti "Bušilice"
- [ ] Dodati specifikacije:
  - Snaga: 600W
  - Težina: 1.8kg
- [ ] Upload bar jedne slike (JPG/PNG)
- [ ] Sačuvati - alat se pojavljuje u listi

### Prikaz u listi
- [ ] Slika se prikazuje kao thumbnail
- [ ] Naziv i slug su vidljivi
- [ ] Kategorija je vidljiva
- [ ] Cena je formatirana sa € simbolom

### Izmena alata
- [ ] Klik na "Izmeni" otvara formu sa svim postojećim podacima
- [ ] Slike se prikazuju sa opcijom brisanja
- [ ] Može se postaviti primarna slika
- [ ] Dodavanje novih slika radi
- [ ] Izmena specifikacija radi
- [ ] Čuvanje izmena radi

### Brisanje
- [ ] Alat bez rezervacija se može obrisati
- [ ] Potvrda pre brisanja

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, označiti kao Done i nastaviti sa M3-Katalog.
