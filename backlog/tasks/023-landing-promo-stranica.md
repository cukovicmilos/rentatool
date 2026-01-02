# Landing Promo Stranica

## Opis
Kreiranje high-converting landing stranice za promociju RentATool servisa. Stranica će biti dostupna na `/promo` ruti i integrisana u postojeći sistem sa mogućnošću da postane glavna index stranica.

## Ciljna publika
- Privatni korisnici (DIY entuzijasti)
- Majstori i zanatlije
- Ljudi koji žele završiti manji posao bez kupovine alata

## Struktura stranice

### 1. Hero Section
- Social proof: "★★★★★ 500+ zadovoljnih korisnika"
- Headline: "Završite posao efikasno sa iznajmljenim alatom"
- Podnaslov: benefit-focused tekst
- Primary CTA: "Pregledaj alate" → kategorije
- Secondary CTA: "Kako funkcioniše?" → scroll do Process
- Placeholder CSS background

### 2. Trust Logos (Brendovi)
- Metabo, Einhell, Parkside, Womax

### 3. Benefits Section (6 benefita)
- Bez kupovine skupog alata
- Profesionalna oprema
- Dostava na adresu
- Fleksibilni rokovi
- Tehnička podrška
- Pristupačne cene

### 4. Process Section (4 koraka)
- Izaberi → Rezerviši → Koristi → Vrati

### 5. Features Section (5 kartica)
- Širok izbor alata
- Online rezervacija 24/7
- Garancija ispravnosti
- Fleksibilno trajanje
- Stručni saveti

### 6. Pricing Section
- Prikaz istaknutih alata (featured=1) sa stvarnim cenama
- CTA linkovi do pojedinačnih alata

### 7. Testimonials Section (7 testimonijala)
- 5 muških, 2 ženska DIY korisnika (srpska imena)
- Projekti: drvo, metal, elektrika, bušenje

### 8. FAQ Section (4 pitanja)
- Šta ako se alat pokvari dok je kod mene?
- Da li je kaucija obavezna za svaki alat?
- Šta ako probijem rok za vraćanje alata?
- Da li postoji tehnička podrška/savetovanje?

### 9. CTA Section
- "Pronađi alat za svoj projekat"
- CTA dugme

### 10. Footer
- Postojeći footer

## Tehnički zahtevi
- Ruta: `/promo`
- Koristi postojeći header/footer
- Postojeće boje i stil
- Responsive dizajn
- Bez tracking-a za sada
- Samo CTA linkovi (bez formi)

## Status: Done
## Labels: frontend, ui
## Milestone: M6-Frontend-Polish

## Implementacija (Završeno)
- `pages/promo.php` - Kompletna landing stranica sa svim sekcijama
- `assets/css/promo.css` - Stilovi za landing stranicu (responsive)
- Automatski routing preko `pages/` foldera
- Prikazuje istaknute alate iz baze (featured=1)
- FAQ accordion sa JavaScript interakcijom
- Smooth scroll za anchor linkove
