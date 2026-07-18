<?php
/**
 * Services Landing Page - Sitni majstorski poslovi
 */

$pageTitle = 'Sitni majstorski poslovi Subotica - Bušenje, sečenje, montaža | ' . SITE_NAME;
$pageDescription = 'Angažujte me za sitne poslove u Subotici: bušenje rupa, sečenje metala i drveta, montaža, lepljenje, manji električarski poslovi. Dolazim kod vas ili radim u svojoj radionici.';
$canonicalUrl = '/usluge';

// Service schema
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'serviceType' => 'Sitni majstorski poslovi',
    'name' => 'Sitni majstorski poslovi u Subotici',
    'description' => 'Bušenje rupa, sečenje metala, drveta i plastike, montaža i demontaža, lepljenje i manji električarski poslovi. Dolazak na adresu ili rad u radionici.',
    'provider' => [
        '@type' => 'LocalBusiness',
        '@id' => 'https://rentatool.in.rs/#organization',
        'name' => SITE_NAME,
        'telephone' => SITE_PHONE,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Gregora Kreka 15',
            'addressLocality' => 'Subotica',
            'addressCountry' => 'RS'
        ]
    ],
    'areaServed' => [
        '@type' => 'City',
        'name' => 'Subotica'
    ],
    'hoursAvailable' => [
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '16:00',
            'closes' => '20:00'
        ],
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'Saturday',
            'opens' => '08:00',
            'closes' => '20:00'
        ]
    ]
];

// FAQ
$faqs = [
    [
        'question' => 'Koliko košta angažovanje za sitne poslove?',
        'answer' => 'Cena se dogovara unapred, po poslu - u zavisnosti od obima i složenosti. Kontaktirajte nas sa opisom posla i dobićete ponudu pre nego što se obavezete.'
    ],
    [
        'question' => 'Da li dolazite na adresu ili radite u radionici?',
        'answer' => 'Obe opcije! Možete doneti materijal u moju radionicu, ili ja dolazim na vašu adresu sa potrebnim alatom i opremom na teritoriji Subotice.'
    ],
    [
        'question' => 'Koje poslove radite?',
        'answer' => 'Bušenje rupa u zidu, betonu i drvetu, sečenje metala, drveta i plastike, manji električarski poslovi, lepljenje i popravke, montaža i demontaža i slično.'
    ],
    [
        'question' => 'Kako da zakažem termin?',
        'answer' => 'Popunite formu na ovoj stranici ili nas pozovite telefonom. Dogovorićemo termin koji vama odgovara - radnim danima od 16:00 do 20:00h i subotom od 08:00 do 20:00h.'
    ]
];

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
$additionalSchemas = [$faqSchema];

// CSS/JS for FAQ accordion
$extraCss = '<link rel="stylesheet" href="' . asset('css/promo.min.css') . '" media="print" onload="this.media=\'all\'"><noscript><link rel="stylesheet" href="' . asset('css/promo.min.css') . '"></noscript>';
$extraJsHead = '<script src="' . asset('js/jb-accordion-lite.min.js') . '" defer></script>';
$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() { initJbAccordionLite({ containerId: "faq-accordion", allowMultiple: false }); });</script>';

// For service modal
$serviceTypeLabels = [
    'drilling' => 'Bušenje',
    'cutting' => 'Sečenje',
    'assembly' => 'Sastavljanje / Montaža',
    'gluing' => 'Lepljenje',
    'repair' => 'Popravka',
    'other' => 'Ostalo'
];
$minDate = date('Y-m-d');

$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
    ['title' => 'Usluge']
];

ob_start();
?>

<div class="services-page">
    <div class="page-header">
        <h1>Sitni majstorski poslovi u Subotici</h1>
        <p class="page-subtitle">
            Treba ti izbušiti rupu, iseći metal, spojiti struju, zalepiti nešto?
            Raspoložen sam za ove i slične poslove — brzo, u dogovorenom roku i poštenoj ceni.
        </p>
    </div>

    <div class="services-options">
        <div class="service-option">
            <div class="service-option-icon">🔧</div>
            <h2>Donesi u radionicu</h2>
            <p>Donesi materijal/stvar kod mene, uradiću posao u svojoj radionici.</p>
        </div>
        <div class="service-option">
            <div class="service-option-icon">🏠</div>
            <h2>Dolazim kod tebe</h2>
            <p>Dolazim na tvoju adresu sa potrebnim alatom i opremom, na teritoriji Subotice.</p>
        </div>
    </div>

    <p class="services-note">
        <strong>Cena se dogovara unapred</strong> — po poslu, u zavisnosti od obima i složenosti.
    </p>

    <button class="btn btn-primary btn-large" id="openServiceModal">
        Naruči uslugu →
    </button>

    <div class="services-examples">
        <h2>Primeri poslova</h2>
        <ul>
            <li>Bušenje rupa u zidu, betonu, drvetu</li>
            <li>Sečenje metala, drveta, plastike</li>
            <li>Manji električarski poslovi</li>
            <li>Lepljenje i popravke</li>
            <li>Montaža i demontaža</li>
            <li>I još mnogo toga...</li>
        </ul>
    </div>

    <!-- FAQ Section -->
    <section class="promo-section promo-faq">
        <h2 class="promo-section-title">Često postavljana pitanja</h2>
        <div id="faq-accordion" class="jb-accordion-lite-container faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="jb-accordion-lite-item faq-item">
                <button class="jb-accordion-lite-header faq-question">
                    <span><?= e($faq['question']) ?></span>
                    <span class="accordion-arrow">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <polyline points="6 8 10 12 14 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>
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
</div>

<?php include TEMPLATES_PATH . '/components/service-modal.php'; ?>

<style>
.services-page {
    max-width: 900px;
    text-align: center;
}
.page-subtitle {
    font-size: var(--font-size-large);
    color: var(--color-gray-600);
    line-height: 1.6;
}
.services-page .services-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
    margin: var(--spacing-xl) 0 var(--spacing-lg);
    text-align: left;
}
.services-page .service-option {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    border: 2px solid var(--border-color);
}
.services-page .service-option-icon {
    font-size: 2.5em;
    margin-bottom: var(--spacing-sm);
}
.services-page .service-option h2 {
    font-size: var(--font-size-large);
    margin: 0 0 var(--spacing-xs) 0;
}
.services-page .service-option p {
    margin: 0;
    font-size: var(--font-size-small);
    color: var(--color-gray-600);
}
.services-page .services-note {
    background: #FFF8E1;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-lg);
}
.services-page .services-examples {
    margin-top: var(--spacing-xl);
    text-align: left;
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
}
.services-page .services-examples h2 {
    font-size: var(--font-size-large);
    margin: 0 0 var(--spacing-sm) 0;
}
.services-page .services-examples ul {
    margin: 0;
    padding-left: var(--spacing-lg);
    columns: 2;
}
.services-page .services-examples li {
    list-style: disc;
    margin-bottom: var(--spacing-xs);
}
.services-page .promo-faq {
    text-align: left;
    margin-top: var(--spacing-xl);
}
@media (max-width: 768px) {
    .services-page .services-options {
        grid-template-columns: 1fr;
    }
    .services-page .services-examples ul {
        columns: 1;
    }
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
