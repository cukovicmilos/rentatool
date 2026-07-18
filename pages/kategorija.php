<?php
/**
 * Category Page - Tools filtered by category
 */

$slug = get('slug', '');

// Get category
$category = db()->fetch("SELECT * FROM categories WHERE slug = ? AND active = 1", [$slug]);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Kategorija nije pronađena';
    $content = '<div class="alert alert-error">Kategorija nije pronađena.</div><p><a href="' . url('') . '">← Nazad na početnu</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// Get parent category if exists
$parentCategory = null;
if ($category['parent_id']) {
    $parentCategory = db()->fetch("SELECT * FROM categories WHERE id = ?", [$category['parent_id']]);
}

// Get subcategories
$subcategories = db()->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM tool_categories tc 
            JOIN tools t ON tc.tool_id = t.id 
            WHERE tc.category_id = c.id AND t.status = 'available') as tool_count
    FROM categories c 
    WHERE c.parent_id = ? AND c.active = 1 
    ORDER BY c.sort_order, c.name
", [$category['id']]);

// Get tools in this category
$tools = db()->fetchAll("
    SELECT t.*,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t
    JOIN tool_categories tc ON t.id = tc.tool_id
    WHERE tc.category_id = ? AND t.status IN ('available', 'rented')
    ORDER BY t.featured DESC, t.name
", [$category['id']]);

// Page settings
$pageTitle = $category['name'] . ' - Iznajmljivanje alata Subotica - Rent a Tool';
$pageDescription = $category['description'] ?? 'Iznajmljivanje ' . $category['name'] . ' u Subotici';
$currentCategorySlug = $slug;

// Category FAQ - dinamička pitanja za SEO
$faqs = [
    [
        'question' => 'Koliko košta iznajmljivanje - ' . $category['name'] . '?',
        'answer' => 'Cene se razlikuju po alatu i prikazane su na kartici svakog alata. Vikendom se naplaćuje 10% više, a za iznajmljivanje od 7 ili više dana odobravamo 10% popusta.'
    ],
    [
        'question' => 'Da li vršite dostavu za kategoriju ' . $category['name'] . ' u Subotici?',
        'answer' => 'Da! Dostava na adresu košta ' . DELIVERY_ONEWAY . ' ' . CURRENCY_SIGN . ', a dostava sa povratom alata ' . DELIVERY_ROUNDTRIP . ' ' . CURRENCY_SIGN . '. Lično preuzimanje je besplatno - radnim danima od 16:00 do 20:00h i subotom od 08:00 do 20:00h.'
    ],
    [
        'question' => 'Kako da rezervišem alat iz kategorije ' . $category['name'] . '?',
        'answer' => 'Odaberite alat, izaberite datume na stranici alata i pošaljite zahtev. Potvrdu rezervacije dobićete emailom. Za hitne rezervacije nas možete pozvati direktno.'
    ],
    [
        'question' => 'Da li mogu da produžim period iznajmljivanja?',
        'answer' => 'Da, dovoljno je da nam se javite pre isteka roka. Produžićemo rezervaciju ukoliko alat nije već rezervisan za drugog korisnika.'
    ]
];

// Structured data: ItemList + FAQPage
$additionalSchemas = [];
if (!empty($tools)) {
    $additionalSchemas[] = itemListSchema($tools, $category['name']);
}

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
$additionalSchemas[] = $faqSchema;

// CSS/JS for FAQ accordion
$extraCss = '<link rel="stylesheet" href="' . asset('css/promo.min.css') . '" media="print" onload="this.media=\'all\'"><noscript><link rel="stylesheet" href="' . asset('css/promo.min.css') . '"></noscript>';
$extraJsHead = '<script src="' . asset('js/jb-accordion-lite.min.js') . '" defer></script>';
$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() { initJbAccordionLite({ containerId: "faq-accordion", allowMultiple: false }); });</script>';

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
    ['title' => 'Svi alati', 'url' => url('alati')],
];
if ($parentCategory) {
    $breadcrumbs[] = ['title' => $parentCategory['name'], 'url' => url('kategorija/' . $parentCategory['slug'])];
}
$breadcrumbs[] = ['title' => $category['name']];

ob_start();
?>

<div class="page-header">
    <h1><?= e($category['name']) ?></h1>
    <?php if ($category['description']): ?>
    <p class="text-muted"><?= e($category['description']) ?></p>
    <?php endif; ?>
    <p class="category-intro">
        Iznajmite <?= e($category['name']) ?> u Subotici i okolini — profesionalna oprema za DIY projekte,
        renoviranje i popravke, bez kupovine i bez brige o održavanju.
        <?php if (!empty($tools)): ?>
        Trenutno u ponudi: <strong><?= count($tools) ?></strong> <?= count($tools) === 1 ? 'alat' : 'alata' ?>.
        <?php endif; ?>
        Lično preuzimanje je besplatno, a dostava je dostupna na teritoriji grada Subotice.
    </p>
</div>

<?php if (!empty($subcategories)): ?>
<div class="subcategories-bar mb-3">
    <strong>Podkategorije:</strong>
    <?php foreach ($subcategories as $sub): ?>
        <a href="<?= url('kategorija/' . $sub['slug']) ?>" class="btn btn-secondary btn-small">
            <?= e($sub['name']) ?>
            <?php if ($sub['tool_count'] > 0): ?>
            <span>(<?= $sub['tool_count'] ?>)</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($tools)): ?>
    <div class="alert alert-info">
        Nema alata u ovoj kategoriji.
    </div>
<?php else: ?>
    <p class="text-muted mb-2">Pronađeno alata: <?= count($tools) ?></p>
    <div class="tools-grid">
        <?php foreach ($tools as $tool): ?>
            <?php include TEMPLATES_PATH . '/components/tool-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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

<style>
.subcategories-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--spacing-sm);
}
.category-intro {
    margin-top: var(--spacing-md);
    line-height: 1.7;
    color: var(--color-gray-600);
    max-width: 800px;
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
