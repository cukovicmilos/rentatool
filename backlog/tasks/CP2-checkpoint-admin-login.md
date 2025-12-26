# CHECKPOINT 2: Admin login

- **Status**: To Do
- **Labels**: checkpoint
- **Milestone**: M2-Admin

## Opis

Provera da admin autentifikacija radi ispravno.

## Testirati

### Login stranica
URL: `http://localhost/rentatool/admin/login.php`

- [ ] Login stranica se prikazuje bez grešaka
- [ ] Login sa ispravnim podacima (`admin` / `admin123`) uspeva
- [ ] Login sa pogrešnim podacima prikazuje grešku
- [ ] Nakon uspešnog logina, redirectuje na dashboard

### Logout
- [ ] Klik na "Odjava" u admin headeru odjavljuje korisnika
- [ ] Nakon logout-a, redirectuje na login stranicu

### Zaštita ruta
- [ ] Direktan pristup `http://localhost/rentatool/admin/` bez logina redirectuje na login
- [ ] Direktan pristup `http://localhost/rentatool/admin/kategorije` bez logina redirectuje na login

## Kada je završeno

Kada korisnik potvrdi da sve stavke rade, označiti kao Done i nastaviti sa CHECKPOINT 3.
