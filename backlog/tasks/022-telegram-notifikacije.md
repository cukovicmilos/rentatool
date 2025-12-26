# Telegram notifikacije za nove rezervacije

- **Status**: Done
- **Labels**: integration, backend
- **Milestone**: M7-Integracije

## Opis

Integracija sa postojecim Telegram botom za obavestenja o novim rezervacijama.

## Prioritet

Nizak - implementirati na kraju projekta.

## Zadaci

- [x] Konfiguracija Telegram Bot tokena i Chat ID-a
- [x] Funkcija za slanje poruke na Telegram
- [x] Slanje obavestenja pri novoj rezervaciji
- [x] Format poruke: broj rezervacije, musterija, alati, datumi, ukupna cena

## Implementacija

### Konfiguracija
U `includes/config.php` dodato:
```php
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');
```

### Funkcije
U `includes/functions.php` dodato:

1. `sendTelegramNotification($message)` - šalje poruku na Telegram API
   - Koristi cURL za HTTP POST request
   - Podržava HTML formatiranje
   - Vraća true/false i loguje greške

2. `formatReservationTelegramMessage($reservation, $items)` - formatira poruku
   - Broj rezervacije
   - Ime, telefon, email mušterije
   - Period rezervacije
   - Opcija dostave i adresa
   - Lista alata sa cenama
   - Ukupna cena i depozit
   - Napomene

### Integracija
U `pages/checkout.php` nakon uspešne rezervacije poziva se:
```php
$telegramMessage = formatReservationTelegramMessage($reservationData, $telegramItems);
sendTelegramNotification($telegramMessage);
```

### Aktivacija
Za aktivaciju, popuniti TELEGRAM_BOT_TOKEN i TELEGRAM_CHAT_ID u config.php
