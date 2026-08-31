<?php
/**
 * Landing Promo Page - High-Converting Landing Page
 * 
 * Sekcije:
 * 1. Hero Section
 * 2. Trust Logos (brendovi)
 * 3. Services Section
 * 4. Pricing Section (istaknuti alati)
 * 5. Benefits Section
 * 6. Process Section
 * 7. Features Section
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

// FAQ data
$faqs = [
    [
        'question' => 'Šta ako se alat pokvari dok je kod mene?',
        'answer' => 'Ne brinite! Ako dođe do kvara pri normalnoj upotrebi, nećete snositi troškove popravke. Samo nas obavestite, a mi ćemo vam ponuditi zamenu ili povrat novca za preostale dane. Naravno, očekujemo da se alat koristi prema uputstvima.'
    ],
    [
        'question' => 'Da li je kaucija obavezna za svaki alat?',
        'answer' => 'Kaucija zavisi od vrednosti alata. Za jednostavnije alate (bušilice, brusilice) kaucija je simbolična ili je nema. Za skuplje alate (šlicarica, usisivač) kaucija je obavezna i vraća se u celosti po vraćanju ispravnog alata.'
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
$pageTitle = 'Iznajmljivanje alata Subotica - Rent a Tool';
$pageDescription = 'Završite posao efikasno sa iznajmljenim alatom. Profesionalna oprema za DIY projekte, renoviranje i popravke. Bez kupovine, bez brige o održavanju.';
$bodyClass = 'promo-page';
$showSidebar = false;

// Build FAQPage Schema
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => []
];

foreach ($faqs as $faq) {
    $faqSchema['mainEntity'][] = [
        '@type' => 'Question',
        'name' => $faq['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['answer']
        ]
    ];
}

$schemaData = $faqSchema;

// ItemList schema for featured tools
if (!empty($featuredTools)) {
    $additionalSchemas = [itemListSchema($featuredTools, 'Najtraženiji alati za iznajmljivanje')];
}

ob_start();
?>

<!-- HERO SECTION -->
<section class="promo-hero">
    <div class="promo-hero-content">
        <div class="promo-hero-text">
            <h1 class="promo-headline">Završite posao efikasno sa iznajmljenim alatom</h1>
            
            <p class="promo-subheadline">
                Ne morate više za svaki posao da tražite majstora. 
                Pogledajte na Youtube kako se radi, iznajmite alat i uštedite novac!
            </p>
            
            <div class="promo-cta-group">
                <a href="<?= url('alati') ?>" class="btn btn-primary btn-large">Pregledaj alate</a>
                <a href="#process" class="btn btn-secondary btn-large">Kako funkcioniše?</a>
            </div>
        </div>
        
        <div class="promo-hero-visual">
            <div class="hero-logo-container">
                <img src="<?= asset('images/rent-a-tool-logo-full.svg') ?>" 
                     alt="<?= SITE_NAME ?>" 
                     class="hero-logo"
                     width="455"
                     height="455"
                     fetchpriority="high">
            </div>
        </div>
    </div>
</section>

<!-- PRICING SECTION (Featured Tools) -->
<section class="promo-section promo-pricing" id="pricing">
    <h2 class="promo-section-title">Najtraženiji alati za iznajmljivanje u Subotici</h2>
    <p class="promo-section-subtitle">Pogledajte neke od naših najtraženijih alata</p>
    
    <?php if (!empty($featuredTools)): ?>
    <div class="pricing-grid">
        <?php foreach ($featuredTools as $index => $tool): ?>
        <a href="<?= url('alat/' . $tool['slug']) ?>" class="pricing-card <?= $index === 1 ? 'featured' : '' ?>">
            <?php if ($index === 1): ?>
            <div class="pricing-badge">Popularan izbor</div>
            <?php endif; ?>
            
            <div class="pricing-image">
                <?php if ($tool['primary_image']): ?>
                <img src="<?= url('uploads/tools/' . $tool['primary_image']) ?>" 
                     alt="<?= e(toolAlt($tool)) ?>"
                     width="280"
                     height="180"
                     loading="lazy">
                <?php else: ?>
                <div class="no-image"><i class="fas fa-wrench"></i></div>
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
            
            <span class="btn <?= $index === 1 ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                Rezerviši
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center text-muted">Trenutno nema istaknutih alata.</p>
    <?php endif; ?>
    
    <div class="pricing-cta">
        <a href="<?= url('alati') ?>" class="btn btn-primary btn-large">Pogledaj sve alate →</a>
    </div>
</section>

<!-- BENEFITS SECTION -->
<section class="promo-section promo-benefits" id="benefits">
    <h2 class="promo-section-title">Zašto iznajmiti umesto kupiti?</h2>
    
    <div class="benefits-grid">
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-piggy-bank"></i></div>
            <h3>Bez kupovine skupog alata</h3>
            <p>Uštedite hiljade dinara - platite samo za dane kada vam alat zaista treba.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-award"></i></div>
            <h3>Kvalitetna oprema</h3>
            <p>Kvalitetni brendovi koji garantuju odličan rezultat vašeg projekta.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-truck"></i></div>
            <h3>Dostava na adresu</h3>
            <p>Ne morate dolaziti - dostavljamo alat direktno na vašu adresu.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-calendar-alt"></i></div>
            <h3>Fleksibilni rokovi</h3>
            <p>Od jednog dana do 10 dana - iznajmite tačno onoliko koliko vam treba.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-headset"></i></div>
            <h3>Tehnička podrška</h3>
            <p>Niste sigurni kako koristiti alat? Tu smo da pomognemo i savetujemo.</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fas fa-check-circle"></i></div>
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
            <div class="feature-icon"><i class="fas fa-toolbox"></i></div>
            <h3>Širok izbor alata</h3>
            <p>Od bušilica i brusilica, do pila, i molerskog alata - sve na jednom mestu.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-globe"></i></div>
            <h3>Online rezervacija 24/7</h3>
            <p>Rezervišite bilo kada, sa bilo kog uređaja. Bez čekanja, bez poziva.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Garancija ispravnosti</h3>
            <p>Svaki alat je testiran pre izdavanja. Garantujemo ispravan rad.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-clock"></i></div>
            <h3>Fleksibilno trajanje</h3>
            <p>Produžite rezervaciju ako vam treba više vremena - jedan poziv je dovoljan.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-comments"></i></div>
            <h3>Stručni saveti</h3>
            <p>Ne znate koji alat vam treba? Pomažemo da odaberete pravi za vaš posao.</p>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="promo-section promo-faq" id="faq">
    <h2 class="promo-section-title">Često postavljana pitanja</h2>

    <div id="faq-accordion" class="jb-accordion-lite-container faq-list">
        <?php foreach ($faqs as $index => $faq): ?>
        <div class="jb-accordion-lite-item faq-item">
            <button class="jb-accordion-lite-header faq-question">
                <span><?= e($faq['question']) ?></span>
                <span class="accordion-arrow">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <polyline points="6 8 10 12 14 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </button>
            <div class="jb-accordion-lite-content faq-answer">
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
        <a href="<?= url('alati') ?>" class="btn btn-primary btn-large">Pronađi alat za svoj projekat</a>
    </div>
</section>

<?php
$content = ob_get_clean();

// Extra CSS for promo page - use minified version with defer loading
$extraCss = '<link rel="stylesheet" href="' . asset('css/promo.min.css') . '" media="print" onload="this.media=\'all\'"><noscript><link rel="stylesheet" href="' . asset('css/promo.min.css') . '"></noscript>';

// Extra JS for FAQ accordion (jb_accordion_lite) + smooth scroll
$extraJs = '
<script src="' . asset('js/jb-accordion-lite.min.js') . '"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    initJbAccordionLite({ containerId: "faq-accordion", allowMultiple: false });

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
?>

<style>
.promo-pricing {
    background: var(--color-gray-100);
    padding: var(--spacing-xl) 0;
}
</style>

<?php
include TEMPLATES_PATH . '/layout.php';
