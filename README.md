# RentaTool

Servis za iznajmljivanje građevinske opreme u Subotici i okolini.

## Demo

🌐 **Live:** [https://labubush.duckdns.org/rentatool/](https://labubush.duckdns.org/rentatool/)

## Tech Stack

- **Backend:** PHP 8.3
- **Database:** SQLite
- **Frontend:** Vanilla CSS, jQuery
- **Dizajn:** Craigslist stil (minimalistički, funkcionalan)

## Funkcionalnosti

### Frontend
- Katalog alata po kategorijama
- Detaljna stranica alata sa galerijom slika
- Kalendar dostupnosti
- Korpa (session-based)
- Checkout sa opcijama dostave
- Otkazivanje rezervacija

### Admin Panel
- CRUD za kategorije
- CRUD za alate (sa slikama i specifikacijama)
- Upravljanje rezervacijama
- Blokirani datumi
- Statičke stranice

### Integracije
- Telegram notifikacije za nove rezervacije
- SEO: sitemap.xml, robots.txt, Open Graph, Schema.org

## Cenovnik

| Pravilo | Vrednost |
|---------|----------|
| Osnovna cena | EUR / 24h |
| Vikend | +10% |
| 7+ dana | -10% popust |
| Max dana | 10 |
| Rezervacija unapred | do 30 dana |
| Otkazivanje | min 2 dana pre |

## Dostava (Subotica i okolina)

| Opcija | Cena |
|--------|------|
| Lično preuzimanje | 0 EUR |
| Samo dostava | 10 EUR |
| Dostava + preuzimanje | 15 EUR |

## Instalacija

### 1. Kloniraj repo
```bash
git clone git@github.com:cukovicmilos/rentatool.git
cd rentatool
```

### 2. Podesi environment
```bash
cp .env.example .env
nano .env  # Popuni svoje vrednosti
```

### 3. Kreiraj bazu
```bash
php database/install.php
```

### 4. Podesi web server
Nginx primer:
```nginx
location /rentatool/ {
    alias /var/www/html/rentatool/;
    try_files $uri $uri/ /rentatool/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

## Struktura projekta

```
rentatool/
├── admin/              # Admin panel
├── api/                # API endpoints (korpa)
├── assets/
│   ├── css/           # Stilovi
│   └── js/            # JavaScript
├── database/
│   ├── schema.sql     # SQL šema
│   └── install.php    # Instalacija baze
├── includes/
│   ├── config.php     # Konfiguracija
│   ├── db.php         # Database klasa
│   └── functions.php  # Helper funkcije
├── pages/              # Frontend stranice
├── templates/          # Layout i komponente
├── uploads/            # Upload-ovane slike
├── .env.example        # Environment šablon
├── index.php           # Front controller
├── robots.txt          # SEO
└── sitemap.php         # Dinamički sitemap
```

## Environment varijable (.env)

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id

# Admin kredencijali
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_password
```

## Licenca

MIT
