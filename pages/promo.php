<?php
/**
 * Landing Promo Page - High-Converting Landing Page
 * 
 * Sekcije:
 * 1. Hero Section
 * 2. Trust Logos (brendovi)
 * 3. Benefits Section
 * 4. Process Section
 * 5. Features Section
 * 6. Pricing Section (istaknuti alati)
 * 7. Testimonials Section
 * 8. FAQ Section
 * 9. CTA Section
 */

// Get featured tools from database
$featuredTools = db()->fetchAll("
    SELECT t.*,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t 
    WHERE t.featured = 1 AND t.status IN ('available', 'rented')
    ORDER BY t.created_at DESC
    LIMIT 6
");

// If no featured tools, get latest available
if (empty($featuredTools)) {
    $featuredTools = db()->fetchAll("
        SELECT t.*,
               (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM tools t 
        WHERE t.status = 'available'
        ORDER BY t.created_at DESC
        LIMIT 6
    ");
}

// Testimonials data
$testimonials = [
    [
        'name' => 'Marko P.',
        'role' => 'DIY entuzijasta',
        'project' => 'Izrada police od drveta',
        'text' => 'Trebala mi je kružna testera za vikend projekat. Umesto da kupujem alat koji ću koristiti jednom godišnje, iznajmio sam za 2 dana i uštedeo 5.000 dinara!',
        'rating' => 5
    ],
    [
        'name' => 'Jovan N.',
        'role' => 'Vlasnik stana',
        'project' => 'Postavljanje lustera',
        'text' => 'Morao sam da premestim luster sa jednog mesta na drugo. Iznajmio sam bušilicu i sve potrebno, posao završen za 2 sata. Odlična usluga!',
        'rating' => 5
    ],
    [
        'name' => 'Ana Đ.',
        'role' => 'Renoviranje stana',
        'project' => 'Bušenje rupa za slike',
        'text' => 'Selila sam se u novi stan i trebalo je okačiti 10 slika. Nikada nisam koristila bušilicu, ali uz savete koje sam dobila, sve je prošlo glatko!',
        'rating' => 5
    ],
    [
        'name' => 'Stefan J.',
        'role' => 'Hobista',
        'project' => 'Izrada stalka od metala',
        'text' => 'Za projekat stalka od metala mi je trebala ručna brusilica - fleks. Alat čist i ispravan. Definitivno ću opet koristiti.',
        'rating' => 5
    ],
    [
        'name' => 'Milan S.',
        'role' => 'Električar amater',
        'project' => 'Kanal za kablovski internet',
        'text' => 'Trebalo je probušiti kanal kroz zid za kablovski internet. Iznajmio sam šlicericu sa usisivačem, dobio i uputstva kako da izbegnem instalacije. Top!',
        'rating' => 4
    ],
    [
        'name' => 'Jelena M.',
        'role' => 'DIY projekti',
        'project' => 'Restauracija starog nameštaja',
        'text' => 'Obnovila sam staru komodu - trebala mi je polirka - šlajferica i nekoliko drugih alata. Sve na jednom mestu, pristupačne cene. Preporučujem!',
        'rating' => 5
    ],
    [
        'name' => 'Dragan I.',
        'role' => 'Majstor za sitne popravke',
        'project' => 'Razni projekti',
        'text' => 'Koristim RentATool već godinu dana za razne projekte. Uvek pouzdani, alati u odličnom stanju. Štedim novac i ne nagomilavam alat koji više nikad neću koristiti ili jako retko.',
        'rating' => 5
    ]
];

// FAQ data
$faqs = [
    [
        'question' => 'Šta ako se alat pokvari dok je kod mene?',
        'answer' => 'Ne brinite! Ako dođe do kvara pri normalnoj upotrebi, nećete snositi troškove popravke. Samo nas obavestite, a mi ćemo vam ponuditi zamenu ili povrat novca za preostale dane. Naravno, očekujemo da se alat koristi prema uputstvima.'
    ],
    [
        'question' => 'Da li je kaucija obavezna za svaki alat?',
        'answer' => 'Kaucija zavisi od vrednosti alata. Za jednostavnije alate (bušilice, brusilice) kaucija je simbolična ili je nema. Za skuplje alate (agregati, kompresori) kaucija je obavezna i vraća se u celosti po vraćanju ispravnog alata.'
    ],
    [
        'question' => 'Šta ako probijem rok za vraćanje alata?',
        'answer' => 'Ako vam treba alat duže, javite nam se pre isteka roka i produžićemo rezervaciju (ako alat nije rezervisan). U slučaju kašnjenja bez najave, naplaćuje se dodatni dan po standardnoj ceni uvećanoj za 20%.'
    ],
    [
        'question' => 'Da li postoji tehnička podrška i savetovanje?',
        'answer' => 'Apsolutno! Pri preuzimanju alata dobićete kratke instrukcije za upotrebu. Takođe, dostupni smo telefonom za sva pitanja tokom korišćenja. Za početnike nudimo i osnovne savete za siguran rad sa alatom.'
    ]
];

// Page settings - no sidebar for landing page
$pageTitle = 'Iznajmi alat za svoj projekat | ' . SITE_NAME;
$pageDescription = 'Završite posao efikasno sa iznajmljenim alatom. Profesionalna oprema za DIY projekte, renoviranje i popravke. Bez kupovine, bez brige o održavanju.';
$bodyClass = 'promo-page';
$showSidebar = false;

ob_start();
?>

<!-- HERO SECTION -->
<section class="promo-hero">
    <div class="promo-hero-content">
        <div class="promo-hero-text">
            <div class="promo-social-proof">
                <span class="stars">★★★★★</span>
                <span class="proof-text">500+ zadovoljnih korisnika</span>
            </div>
            
            <h1 class="promo-headline">Završite posao efikasno sa iznajmljenim alatom</h1>
            
            <p class="promo-subheadline">
                Ne morate više za svaki posao da tražite majstora. 
                Pogledajte na Youtube kako se radi, iznajmite alat i uštedite novac!
            </p>
            
            <div class="promo-cta-group">
                <a href="<?= url('') ?>" class="btn btn-primary btn-large">Pregledaj alate</a>
                <a href="#process" class="btn btn-secondary btn-large">Kako funkcioniše?</a>
            </div>
        </div>
        
        <div class="promo-hero-visual">
            <div class="hero-image-placeholder">
                <span class="hero-icon">🔧</span>
                <span class="hero-icon">🔨</span>
                <span class="hero-icon">⚡</span>
            </div>
        </div>
    </div>
</section>

<!-- TRUST LOGOS (BRENDOVI) -->
<section class="promo-trust">
    <p class="trust-label">Brendovi u našoj ponudi</p>
    <div class="trust-logos">
        <div class="trust-logo">
            <span class="brand-name">Metabo</span>
        </div>
        <div class="trust-logo">
            <span class="brand-name">Einhell</span>
        </div>
        <div class="trust-logo">
            <span class="brand-name">Parkside</span>
        </div>
        <div class="trust-logo">
            <span class="brand-name">Womax</span>
        </div>
    </div>
</section>

<!-- BENEFITS SECTION -->
<section class="promo-section promo-benefits" id="benefits">
    <h2 class="promo-section-title">Zašto iznajmiti umesto kupiti?</h2>
    
    <div class="benefits-grid">
        <div class="benefit-card">
            <div class="benefit-icon">💰</div>
            <h3>Bez kupovine skupog alata</h3>
            <p>Uštedite hiljade dinara - platite samo za dane kada vam alat zaista treba.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">🏆</div>
            <h3>Profesionalna oprema</h3>
            <p>Kvalitetni brendovi koji garantuju odličan rezultat vašeg projekta.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">🚚</div>
            <h3>Dostava na adresu</h3>
            <p>Ne morate dolaziti - dostavljamo alat direktno na vašu adresu.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">📅</div>
            <h3>Fleksibilni rokovi</h3>
            <p>Od jednog dana do 10 dana - iznajmite tačno onoliko koliko vam treba.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">🛠️</div>
            <h3>Tehnička podrška</h3>
            <p>Niste sigurni kako koristiti alat? Tu smo da pomognemo i savetujemo.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">✅</div>
            <h3>Pristupačne cene</h3>
            <p>Transparentne cene bez skrivenih troškova. Znate tačno koliko plaćate.</p>
        </div>
    </div>
</section>

<!-- PROCESS SECTION -->
<section class="promo-section promo-process" id="process">
    <h2 class="promo-section-title">Kako funkcioniše?</h2>
    <p class="promo-section-subtitle">Jednostavan proces u 4 koraka</p>
    
    <div class="process-steps">
        <div class="process-step">
            <div class="step-number">1</div>
            <h3>Izaberi</h3>
            <p>Pregledaj katalog i izaberi alat koji ti treba za projekat.</p>
            <span class="step-time">~2 minuta</span>
        </div>
        
        <div class="process-connector"></div>
        
        <div class="process-step">
            <div class="step-number">2</div>
            <h3>Rezerviši</h3>
            <p>Odaberi datume i potvrdi rezervaciju online.</p>
            <span class="step-time">~3 minuta</span>
        </div>
        
        <div class="process-connector"></div>
        
        <div class="process-step">
            <div class="step-number">3</div>
            <h3>Koristi</h3>
            <p>Preuzmi alat ili ga primi na adresu i završi svoj projekat.</p>
            <span class="step-time">Tvoje vreme</span>
        </div>
        
        <div class="process-connector"></div>
        
        <div class="process-step">
            <div class="step-number">4</div>
            <h3>Vrati</h3>
            <p>Vrati alat po isteku roka. Mi brinemo o čišćenju i održavanju.</p>
            <span class="step-time">~5 minuta</span>
        </div>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="promo-section promo-features" id="features">
    <h2 class="promo-section-title">Šta dobijate sa nama?</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📦</div>
            <h3>Širok izbor alata</h3>
            <p>Od bušilica i brusilica do agregata i kompresora - sve na jednom mestu.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🌐</div>
            <h3>Online rezervacija 24/7</h3>
            <p>Rezervišite bilo kada, sa bilo kog uređaja. Bez čekanja, bez poziva.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔍</div>
            <h3>Garancija ispravnosti</h3>
            <p>Svaki alat je testiran pre izdavanja. Garantujemo ispravan rad.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h3>Fleksibilno trajanje</h3>
            <p>Produžite rezervaciju ako vam treba više vremena - jedan poziv je dovoljan.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">💬</div>
            <h3>Stručni saveti</h3>
            <p>Ne znate koji alat vam treba? Pomažemo da odaberete pravi za vaš posao.</p>
        </div>
    </div>
</section>

<!-- PRICING SECTION (Featured Tools) -->
<section class="promo-section promo-pricing" id="pricing">
    <h2 class="promo-section-title">Popularni alati</h2>
    <p class="promo-section-subtitle">Pogledajte neke od naših najpopularnijih alata</p>
    
    <?php if (!empty($featuredTools)): ?>
    <div class="pricing-grid">
        <?php foreach ($featuredTools as $index => $tool): ?>
        <div class="pricing-card <?= $index === 1 ? 'featured' : '' ?>">
            <?php if ($index === 1): ?>
            <div class="pricing-badge">Popularan izbor</div>
            <?php endif; ?>
            
            <div class="pricing-image">
                <?php if ($tool['primary_image']): ?>
                <img src="<?= url('uploads/tools/' . $tool['primary_image']) ?>" alt="<?= e($tool['name']) ?>">
                <?php else: ?>
                <div class="no-image">🔧</div>
                <?php endif; ?>
            </div>
            
            <h3 class="pricing-title"><?= e($tool['name']) ?></h3>
            
            <?php if (!empty($tool['short_description'])): ?>
            <p class="pricing-description"><?= e(truncate($tool['short_description'], 100)) ?></p>
            <?php endif; ?>
            
            <div class="pricing-price">
                <span class="price-amount"><?= formatPrice($tool['price_24h']) ?></span>
                <span class="price-period">/ dan</span>
            </div>
            
            <?php if ($tool['deposit'] > 0): ?>
            <p class="pricing-deposit">Kaucija: <?= formatPrice($tool['deposit']) ?></p>
            <?php endif; ?>
            
            <a href="<?= url('alat/' . $tool['slug']) ?>" class="btn <?= $index === 1 ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                Rezerviši
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center text-muted">Trenutno nema istaknutih alata.</p>
    <?php endif; ?>
    
    <div class="pricing-cta">
        <a href="<?= url('') ?>" class="btn btn-primary btn-large">Pogledaj sve alate →</a>
    </div>
</section>

<!-- TESTIMONIALS SECTION -->
<section class="promo-section promo-testimonials" id="testimonials">
    <h2 class="promo-section-title">Šta kažu naši korisnici?</h2>
    <p class="promo-section-subtitle">Realni projekti, realni ljudi</p>
    
    <div class="testimonials-grid">
        <?php foreach ($testimonials as $testimonial): ?>
        <div class="testimonial-card">
            <div class="testimonial-header">
                <div class="testimonial-avatar">
                    <?= mb_substr($testimonial['name'], 0, 1) ?>
                </div>
                <div class="testimonial-info">
                    <h4><?= e($testimonial['name']) ?></h4>
                    <p><?= e($testimonial['project']) ?></p>
                </div>
            </div>
            <p class="testimonial-text">"<?= e($testimonial['text']) ?>"</p>
            <div class="testimonial-rating">
                <?= str_repeat('★', $testimonial['rating']) ?><?= str_repeat('☆', 5 - $testimonial['rating']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="promo-section promo-faq" id="faq">
    <h2 class="promo-section-title">Često postavljana pitanja</h2>
    
    <div class="faq-list">
        <?php foreach ($faqs as $index => $faq): ?>
        <div class="faq-item">
            <button class="faq-question" aria-expanded="false" data-faq="<?= $index ?>">
                <span><?= e($faq['question']) ?></span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer" id="faq-answer-<?= $index ?>">
                <p><?= e($faq['answer']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA SECTION -->
<section class="promo-section promo-cta-section">
    <div class="cta-content">
        <h2>Spremni za vaš sledeći projekat?</h2>
        <p>Pronađite pravi alat i počnite danas. Bez obaveza, bez skrivenih troškova.</p>
        <a href="<?= url('') ?>" class="btn btn-primary btn-large">Pronađi alat za svoj projekat</a>
    </div>
</section>

<?php
$content = ob_get_clean();

// Extra CSS for promo page
$extraCss = '<link rel="stylesheet" href="' . asset('css/promo.css') . '">';

// Extra JS for FAQ accordion
$extraJs = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // FAQ Accordion
    document.querySelectorAll(".faq-question").forEach(function(button) {
        button.addEventListener("click", function() {
            const faqItem = this.closest(".faq-item");
            const isOpen = faqItem.classList.contains("open");
            
            // Close all
            document.querySelectorAll(".faq-item").forEach(function(item) {
                item.classList.remove("open");
                item.querySelector(".faq-question").setAttribute("aria-expanded", "false");
            });
            
            // Open clicked if was closed
            if (!isOpen) {
                faqItem.classList.add("open");
                this.setAttribute("aria-expanded", "true");
            }
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll(\'a[href^="#"]\').forEach(function(anchor) {
        anchor.addEventListener("click", function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });
});
</script>
';

include TEMPLATES_PATH . '/layout.php';
