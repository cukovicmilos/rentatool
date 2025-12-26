# CHECKPOINT 7: Finalno testiranje

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M6-Frontend-Polish

## Opis

Finalna provera svih funkcionalnosti pre završetka projekta.

## Testirati

### Statičke stranice
- [ ] `/rentatool/stranica/o-nama` - prikazuje sadržaj
- [ ] `/rentatool/stranica/kontakt` - prikazuje sadržaj
- [ ] `/rentatool/stranica/uslovi-koriscenja` - prikazuje sadržaj
- [ ] Linkovi u header navigaciji rade

### Pregled rezervacije
- [ ] Napravi novu test rezervaciju
- [ ] Na Thank You stranici klikni "Pogledajte ili otkažite rezervaciju"
- [ ] Stranica `/rentatool/rezervacija/{KOD}` prikazuje detalje
- [ ] Status rezervacije je vidljiv
- [ ] Svi podaci (kupac, alati, cene) su ispravni

### Otkazivanje rezervacije
- [ ] Na stranici rezervacije klikni "Otkaži rezervaciju"
- [ ] Potvrdi otkazivanje
- [ ] Status se menja u "Otkazana"
- [ ] Otkazana rezervacija više ne može biti otkazana ponovo

### Admin panel - rezervacije
- [ ] `/rentatool/admin/rezervacije` prikazuje sve rezervacije
- [ ] Nova rezervacija je vidljiva sa statusom "pending"
- [ ] Otkazana rezervacija ima status "cancelled"
- [ ] Promena statusa iz dropdown-a radi
- [ ] Detalji rezervacije prikazuju sve informacije

### Kompletni flow (end-to-end)
- [ ] Dodaj alat u korpu sa odabranim datumima
- [ ] Idi na korpu, proveri stavke
- [ ] Idi na checkout, popuni podatke
- [ ] Izaberi opciju dostave
- [ ] Potvrdi rezervaciju
- [ ] Proveri Thank You stranicu
- [ ] Proveri email (ako je konfigurisan)
- [ ] Proveri u admin panelu

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, projekat je spreman za produkciju.
