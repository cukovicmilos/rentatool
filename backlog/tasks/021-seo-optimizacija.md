# SEO optimizacija

- **Status**: Done
- **Labels**: seo, frontend
- **Milestone**: M6-Frontend-Polish

## Opis

Implementirati osnovnu SEO optimizaciju.

## Zadaci

- [x] Meta tagovi (title, description) za sve stranice
- [x] Open Graph tagovi
- [x] Semantic HTML (header, main, article, aside, footer)
- [x] Alt atributi za sve slike
- [x] Sitemap.xml generator
- [x] Robots.txt
- [x] Canonical URLs
- [x] Strukturirani podaci (Schema.org za Product)

## Implementacija

### Open Graph i Meta tagovi
Dodato u `templates/layout.php`:
- Meta description, author, geo tags
- Open Graph (og:title, og:description, og:image, og:url, og:locale)
- Twitter card meta tags
- Canonical URL link

### Sitemap
Kreiran `sitemap.php` koji dinamički generiše XML sitemap sa:
- Početnom stranicom (priority 1.0)
- Kategorijama (priority 0.8)
- Alatima sa lastmod datumom (priority 0.9)
- Statičkim stranicama (priority 0.5)

### Robots.txt
Kreiran `robots.txt` sa pravilima:
- Allow svih crawlera
- Disallow: /admin/, /api/, /database/, /includes/, /templates/, /backlog/
- Allow: /assets/, /uploads/
- Link na sitemap

### Schema.org
Dodato u `pages/alat.php` za svaki alat:
- @type: Product
- name, description, sku, brand
- offers (price, availability, seller)
- image, additionalProperty (specifications)
