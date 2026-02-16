<?php
/**
 * Tool Detail Page
 */

$slug = get('slug', '');

// Get tool
$tool = db()->fetch("SELECT * FROM tools WHERE slug = ?", [$slug]);

if (!$tool) {
    http_response_code(404);
    $pageTitle = 'Alat nije pronađen';
    $content = '<div class="alert alert-error">Alat nije pronađen.</div><p><a href="' . url('') . '">← Nazad na početnu</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// Get tool images
$images = db()->fetchAll("SELECT * FROM tool_images WHERE tool_id = ? ORDER BY is_primary DESC, sort_order", [$tool['id']]);

// Get tool specifications
$specs = db()->fetchAll("SELECT * FROM tool_specifications WHERE tool_id = ? ORDER BY sort_order", [$tool['id']]);

// Get tool categories
$categories = db()->fetchAll("
    SELECT c.* FROM categories c
    JOIN tool_categories tc ON c.id = tc.category_id
    WHERE tc.tool_id = ?
", [$tool['id']]);

// Get tool videos
$videos = db()->fetchAll("SELECT * FROM tool_videos WHERE tool_id = ? ORDER BY sort_order", [$tool['id']]);

// Get blocked dates for this tool (next 30 days)
$blockedDates = db()->fetchAll("
    SELECT blocked_date FROM blocked_dates 
    WHERE (tool_id = ? OR tool_id IS NULL)
    AND blocked_date >= DATE('now')
    AND blocked_date <= DATE('now', '+30 days')
", [$tool['id']]);
$blockedDatesArray = array_column($blockedDates, 'blocked_date');

// Get reserved dates from confirmed reservations
$reservedDates = db()->fetchAll("
    SELECT r.date_start, r.date_end 
    FROM reservations r
    JOIN reservation_items ri ON r.id = ri.reservation_id
    WHERE ri.tool_id = ? 
    AND r.status IN ('pending', 'confirmed')
    AND r.date_end >= DATE('now')
", [$tool['id']]);

// Build array of all unavailable dates
$unavailableDates = $blockedDatesArray;
foreach ($reservedDates as $res) {
    $dates = getDatesBetween($res['date_start'], $res['date_end']);
    $unavailableDates = array_merge($unavailableDates, $dates);
}
$unavailableDates = array_unique($unavailableDates);

// Calculate prices
$weekendPrice = $tool['price_24h'] * (1 + WEEKEND_MARKUP);

// Page settings
$pageTitle = $tool['name'] . ' - ' . SITE_NAME;
$pageDescription = $tool['short_description'] ?? $tool['name'] . ' za iznajmljivanje';

// Open Graph image
$pageImage = !empty($images) ? '/uploads/tools/' . $images[0]['filename'] : null;

// Canonical URL
$canonicalUrl = '/alat/' . $tool['id'] . '/' . slugify($tool['name']);

// Schema.org structured data for Product/Offer
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $tool['name'],
    'description' => $tool['description'] ?? $tool['short_description'] ?? '',
    'sku' => 'TOOL-' . $tool['id'],
    'brand' => [
        '@type' => 'Brand',
        'name' => SITE_NAME
    ],
    'offers' => [
        '@type' => 'Offer',
        'url' => 'https://labubush.duckdns.org' . BASE_URL . $canonicalUrl,
        'priceCurrency' => 'EUR',
        'price' => number_format($tool['price_24h'], 2, '.', ''),
        'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
        'availability' => $tool['status'] === 'available' 
            ? 'https://schema.org/InStock' 
            : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/UsedCondition',
        'seller' => [
            '@type' => 'LocalBusiness',
            'name' => SITE_NAME,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Subotica',
                'addressCountry' => 'RS'
            ]
        ]
    ]
];

// Add image if available
if (!empty($images)) {
    $schemaData['image'] = 'https://labubush.duckdns.org' . BASE_URL . '/uploads/tools/' . $images[0]['filename'];
}

// Add specifications as additionalProperty
if (!empty($specs)) {
    $schemaData['additionalProperty'] = [];
    foreach ($specs as $spec) {
        $schemaData['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name' => $spec['spec_name'],
            'value' => $spec['spec_value']
        ];
    }
}

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
];
if (!empty($categories)) {
    $breadcrumbs[] = ['title' => $categories[0]['name'], 'url' => url('kategorija/' . $categories[0]['slug'])];
}
$breadcrumbs[] = ['title' => $tool['name']];

ob_start();
?>

<div class="tool-detail">
    <div class="tool-detail-grid">
        
        <!-- Gallery -->
        <div class="tool-gallery">
            <?php if (!empty($images)): ?>
            <div class="gallery-main" onclick="openLightbox(currentImageIndex)" style="cursor:zoom-in">
                <img src="<?= upload('tools/' . $images[0]['filename']) ?>"
                     alt="<?= e($tool['name']) ?>"
                     id="mainImage"
                     width="800"
                     height="600"
                     fetchpriority="high">
                <div class="gallery-zoom-hint">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                </div>
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                     onclick="changeImage('<?= upload('tools/' . $img['filename']) ?>', this, <?= $i ?>)">
                    <img src="<?= upload('tools/' . $img['filename']) ?>"
                         alt="<?= e($tool['name']) ?> - slika <?= $i + 1 ?>"
                         width="80"
                         height="60"
                         loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="gallery-main no-image">
                <span>Nema slike</span>
            </div>
            <?php endif; ?>

            <!-- Lightbox -->
            <?php if (!empty($images)): ?>
            <div class="lightbox" id="lightbox" role="dialog" aria-label="Galerija slika" aria-modal="true">
                <div class="lightbox-backdrop" onclick="closeLightbox()"></div>
                <button class="lightbox-close" onclick="closeLightbox()" aria-label="Zatvori galeriju">&times;</button>
                <?php if (count($images) > 1): ?>
                <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)" aria-label="Prethodna slika">&#8249;</button>
                <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)" aria-label="Sledeća slika">&#8250;</button>
                <?php endif; ?>
                <div class="lightbox-content">
                    <img src="" alt="" id="lightboxImage" class="lightbox-image">
                </div>
                <?php if (count($images) > 1): ?>
                <div class="lightbox-counter" id="lightboxCounter"></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($tool['short_description'])): ?>
            <div class="tool-short-description">
                <?= nl2br(e($tool['short_description'])) ?>
            </div>
            <?php endif; ?>

            <?php if ($tool['description']): ?>
            <div class="tool-description">
                <div class="description-content">
                    <?= nl2br(e($tool['description'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Info -->
        <div class="tool-info">
            <h1 class="tool-title"><?= e($tool['name']) ?></h1>
            
            <?php if ($tool['status'] !== 'available'): ?>
            <div class="alert alert-warning mb-2">
                <?php if ($tool['status'] === 'rented'): ?>
                    Ovaj alat je trenutno iznajmljen.
                <?php elseif ($tool['status'] === 'maintenance'): ?>
                    Ovaj alat je trenutno u servisu.
                <?php else: ?>
                    Ovaj alat trenutno nije dostupan.
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="tool-price-box">
                <div class="price-main">
                    <span class="price-label">Cena:</span>
                    <span class="price-value"><?= formatPrice($tool['price_24h']) ?></span>
                    <span class="price-period">/ 24h</span>
                </div>
                <div class="price-weekend">
                    <span class="price-label">Vikend cena:</span>
                    <span class="price-value"><?= formatPrice($weekendPrice) ?></span>
                    <span class="price-note">(+<?= WEEKEND_MARKUP * 100 ?>%)</span>
                </div>
                <?php if ($tool['deposit'] > 0): ?>
                <div class="price-deposit">
                    <span class="price-label">Depozit:</span>
                    <span class="price-value"><?= formatPrice($tool['deposit']) ?></span>
                </div>
                <?php endif; ?>
                <div class="price-discount">
                    <small class="text-success">7+ dana = <?= WEEKLY_DISCOUNT * 100 ?>% popusta!</small>
                </div>
            </div>
            
            <?php if ($tool['status'] === 'available'): ?>
            <!-- Date Selection -->
            <div class="date-selection mt-3" role="group" aria-labelledby="date-selection-heading">
                <h3 id="date-selection-heading">Izaberite period iznajmljivanja</h3>
                <p id="date-help" class="sr-only">Izaberite početni i krajnji datum iznajmljivanja. Datumi označeni narandžasto su već rezervisani.</p>

                <div class="date-calendar-wrapper">
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="date_start" class="form-label">Od:</label>
                            <input type="date" id="date_start" class="form-control date-input-start"
                                   min="<?= date('Y-m-d') ?>"
                                   max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                   aria-describedby="date-help"
                                   aria-label="Datum početka iznajmljivanja"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="date_end" class="form-label">Do:</label>
                            <input type="date" id="date_end" class="form-control date-input-end"
                                   min="<?= date('Y-m-d') ?>"
                                   max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                   aria-describedby="date-help"
                                   aria-label="Datum završetka iznajmljivanja"
                                   required>
                        </div>
                    </div>

                    <?php if (!empty($unavailableDates)): ?>
                    <div class="calendar-wrapper">
                        <div id="miniCalendar" class="mini-calendar" role="img" aria-label="Kalendar dostupnosti za narednih 30 dana"></div>
                        
                        <!-- Calendar Legend -->
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <span class="legend-color legend-reserved"></span>
                                <span class="legend-label">Rezervisano</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color legend-selected"></span>
                                <span class="legend-label">Vaš izbor</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div id="priceCalculation" class="price-calculation" style="display: none;">
                    <div class="calc-row">
                        <span>Broj dana:</span>
                        <span id="calcDays">0</span>
                    </div>
                    <div class="calc-row">
                        <span>Redovni dani:</span>
                        <span id="calcRegular">0 × <?= formatPrice($tool['price_24h']) ?></span>
                    </div>
                    <div class="calc-row">
                        <span>Vikend dani:</span>
                        <span id="calcWeekend">0 × <?= formatPrice($weekendPrice) ?></span>
                    </div>
                    <div class="calc-row" id="calcDiscountRow" style="display: none;">
                        <span>Popust (7+ dana):</span>
                        <span id="calcDiscount" class="text-success">-0 €</span>
                    </div>
                    <div class="calc-row calc-total">
                        <span>Ukupno:</span>
                        <span id="calcTotal">0 €</span>
                    </div>
                </div>
                
                <button type="button" id="addToCartBtn" class="btn btn-primary btn-large btn-block mt-2" disabled
                        aria-label="Rezerviši <?= e($tool['name']) ?>"
                        aria-disabled="true">
                    <span aria-hidden="true">Rezerviši</span>
                    <span class="sr-only">Rezerviši <?= e($tool['name']) ?> za izabrani period</span>
                </button>
                <p class="text-muted text-center mt-1">
                    <small>Max <?= MAX_RENTAL_DAYS ?> dana, rezervacija do <?= MAX_ADVANCE_DAYS ?> dana unapred</small>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Video Section (Full Width) -->
    <?php if (!empty($videos)): ?>
    <div class="tool-videos mt-4">
        <h2>Video materijali</h2>
        <div class="videos-grid">
            <?php foreach ($videos as $video): 
                $videoId = getYouTubeVideoId($video['youtube_url']);
                if ($videoId):
            ?>
            <div class="video-item">
                <?php if (!empty($video['title'])): ?>
                <h3 class="video-title"><?= e($video['title']) ?></h3>
                <?php endif; ?>
                <div class="video-container">
                    <iframe 
                        src="https://www.youtube.com/embed/<?= e($videoId) ?>" 
                        frameborder="0" 
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Description & Specs -->
    <div class="tool-details-section mt-4">
        <?php if (!empty($specs)): ?>
        <div class="tool-specs">
            <h2>Specifikacije</h2>
            <table class="specs-table">
                <?php foreach ($specs as $spec): ?>
                <tr>
                    <th><?= e($spec['spec_name']) ?></th>
                    <td><?= e($spec['spec_value']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.tool-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xl);
}

@media (max-width: 768px) {
    .tool-detail-grid {
        grid-template-columns: 1fr;
    }
}

.gallery-main {
    width: 100%;
    aspect-ratio: 4/3;
    background: #fff;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.gallery-main:not(.no-image) {
    position: relative;
}

.gallery-zoom-hint {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0,0,0,0.55);
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
}

.gallery-main:hover .gallery-zoom-hint {
    opacity: 1;
}

/* Lightbox */
.lightbox {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.lightbox.active {
    display: flex;
}

.lightbox-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.92);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.lightbox-close {
    position: absolute;
    top: 16px;
    right: 20px;
    z-index: 10;
    background: none;
    border: none;
    color: #fff;
    font-size: 40px;
    line-height: 1;
    cursor: pointer;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.lightbox-close:hover {
    background: rgba(255,255,255,0.12);
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    font-size: 48px;
    line-height: 1;
    width: 52px;
    height: 72px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s;
}

.lightbox-nav:hover {
    background: rgba(255,255,255,0.18);
}

.lightbox-prev { left: 16px; }
.lightbox-next { right: 16px; }

.lightbox-content {
    position: relative;
    z-index: 5;
    max-width: 90vw;
    max-height: 88vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-image {
    max-width: 90vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.5);
    animation: lightboxFadeIn 0.2s ease-out;
}

@keyframes lightboxFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.lightbox-counter {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    color: rgba(255,255,255,0.7);
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 1px;
    background: rgba(0,0,0,0.5);
    padding: 6px 16px;
    border-radius: 20px;
}

@media (max-width: 768px) {
    .lightbox-nav {
        width: 40px;
        height: 56px;
        font-size: 36px;
    }
    .lightbox-prev { left: 8px; }
    .lightbox-next { right: 8px; }
    .lightbox-image {
        max-width: 95vw;
        max-height: 80vh;
    }
}

.gallery-main.no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-gray-400);
}

.gallery-thumbs {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-sm);
    overflow-x: auto;
}

.gallery-thumb {
    flex-shrink: 0;
    width: 80px;
    height: 60px;
    border-radius: var(--border-radius);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    opacity: 0.7;
    transition: all 0.2s;
}

.gallery-thumb:hover,
.gallery-thumb.active {
    opacity: 1;
    border-color: var(--color-accent);
}

.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tool-short-description {
    margin-top: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--color-gray-100);
    border-radius: var(--border-radius);
    font-size: var(--font-size-base);
    line-height: 1.6;
    color: var(--color-gray-600);
}

.tool-title {
    margin-bottom: var(--spacing-md);
}

.tool-price-box {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--color-accent);
}

.price-main {
    font-size: 1.5em;
    margin-bottom: var(--spacing-sm);
}

.price-main .price-value {
    font-weight: 700;
    color: var(--color-black);
}

.price-weekend, .price-deposit {
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-xs);
}

.price-label {
    margin-right: var(--spacing-sm);
}

.price-note {
    font-size: var(--font-size-small);
    color: var(--color-gray-500);
}

.date-selection {
    background: var(--color-white);
    border: 1px solid var(--border-color);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
}

.calendar-legend {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs);
    background: var(--color-gray-100);
    border-radius: var(--border-radius);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.legend-color {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}

.legend-reserved {
    background: #FF9933;
}

.legend-selected {
    background: #28A745;
}

.legend-label {
    font-size: 10px;
    color: var(--color-gray-600);
    white-space: nowrap;
}

.date-calendar-wrapper {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--spacing-lg);
    align-items: start;
}

.date-inputs {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.calendar-wrapper {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.date-input-start:focus,
.date-input-end:focus {
    border-color: #28A745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
}

.date-input-start.has-selection,
.date-input-end.has-selection {
    border-color: #28A745;
    background-color: rgba(40, 167, 69, 0.05);
}

.mini-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    font-size: var(--font-size-xs);
    max-width: 140px;
}

.mini-calendar-day {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 2px;
    border: 1px solid var(--border-color);
    background: var(--color-white);
    font-size: 9px;
    padding: 0;
}

.mini-calendar-day.header {
    background: var(--color-gray-200);
    font-weight: 600;
    border: none;
}

.mini-calendar-day.reserved {
    background: #FF9933;
    color: var(--color-white);
    font-weight: 600;
}

.mini-calendar-day.selected {
    background: #28A745;
    color: var(--color-white);
    font-weight: 600;
}

.mini-calendar-day.today {
    border-color: var(--color-accent);
    border-width: 2px;
}

.mini-calendar-day.outside {
    background: var(--color-gray-100);
    color: var(--color-gray-400);
}

.price-calculation {
    background: var(--color-gray-100);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-top: var(--spacing-md);
}

.calc-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-xs) 0;
}

.calc-total {
    border-top: 2px solid var(--color-accent);
    margin-top: var(--spacing-sm);
    padding-top: var(--spacing-sm);
    font-weight: 700;
    font-size: 1.2em;
}

.tool-videos {
    width: 100%;
    max-width: 100%;
}

.tool-videos h2 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-accent);
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-lg);
}

.video-item {
    background: var(--color-gray-100);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.video-title {
    font-size: var(--font-size-base);
    padding: var(--spacing-md);
    margin: 0;
    background: var(--color-white);
    border-bottom: 1px solid var(--border-color);
}

.video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    background: var(--color-gray-100);
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

@media (max-width: 480px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
}

.tool-details-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-xl);
}

@media (max-width: 768px) {
    .tool-details-section {
        grid-template-columns: 1fr;
    }
}

.tool-description {
    margin-top: var(--spacing-md);
}

.tool-description h2,
.tool-specs h2 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-accent);
}

.specs-table {
    width: 100%;
}

.specs-table th,
.specs-table td {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.specs-table th {
    text-align: left;
    font-weight: 600;
    width: 40%;
}

@media (max-width: 768px) {
    .date-calendar-wrapper {
        grid-template-columns: 1fr;
    }
    
    .date-inputs {
        gap: var(--spacing-sm);
    }
    
    .calendar-wrapper {
        margin-top: var(--spacing-md);
    }
    
    .mini-calendar {
        max-width: 120px;
    }
    
    .mini-calendar-day {
        width: 16px;
        height: 16px;
        font-size: 8px;
    }
    
    .legend-label {
        font-size: 9px;
    }
}
</style>

<script>
// Gallery images array
const galleryImages = <?= json_encode(array_map(function($img) use ($tool) {
    return [
        'src' => '/rentatool/uploads/tools/' . $img['filename'],
        'alt' => $tool['name'] . ' - slika'
    ];
}, $images)) ?>;
let currentImageIndex = 0;

function changeImage(src, thumb, index) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
    if (typeof index !== 'undefined') currentImageIndex = index;
}

// Lightbox
function openLightbox(index) {
    const lb = document.getElementById('lightbox');
    if (!lb || !galleryImages.length) return;
    currentImageIndex = index || 0;
    updateLightboxImage();
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lb = document.getElementById('lightbox');
    if (lb) lb.classList.remove('active');
    document.body.style.overflow = '';
}

function lightboxNav(dir) {
    currentImageIndex = (currentImageIndex + dir + galleryImages.length) % galleryImages.length;
    updateLightboxImage();
    // Sync thumbnail
    const thumbs = document.querySelectorAll('.gallery-thumb');
    if (thumbs[currentImageIndex]) {
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        thumbs[currentImageIndex].classList.add('active');
        document.getElementById('mainImage').src = galleryImages[currentImageIndex].src;
    }
}

function updateLightboxImage() {
    const img = document.getElementById('lightboxImage');
    const counter = document.getElementById('lightboxCounter');
    if (!img) return;
    img.src = galleryImages[currentImageIndex].src;
    img.alt = galleryImages[currentImageIndex].alt;
    if (counter) counter.textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('lightbox');
    if (!lb || !lb.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') lightboxNav(-1);
    if (e.key === 'ArrowRight') lightboxNav(1);
});

// Touch swipe support
(function() {
    let touchStartX = 0;
    const lb = document.getElementById('lightbox');
    if (!lb) return;
    lb.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, {passive: true});
    lb.addEventListener('touchend', function(e) {
        const diff = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(diff) > 50) {
            lightboxNav(diff > 0 ? -1 : 1);
        }
    }, {passive: true});
})();

// Date calculation
const toolPrice = <?= $tool['price_24h'] ?>;
const weekendPrice = <?= $weekendPrice ?>;
const weekendMarkup = <?= WEEKEND_MARKUP ?>;
const weeklyDiscount = <?= WEEKLY_DISCOUNT ?>;
const maxDays = <?= MAX_RENTAL_DAYS ?>;
const unavailableDates = <?= json_encode($unavailableDates) ?>;
const toolId = <?= $tool['id'] ?>;
const toolName = <?= json_encode($tool['name']) ?>;

// Helper function to format date as YYYY-MM-DD in local timezone
function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Helper function to parse date string to local date
function parseLocalDate(dateStr) {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
}

// Generate mini calendar
function generateMiniCalendar() {
    const calendar = document.getElementById('miniCalendar');
    if (!calendar) return;
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const endDate = new Date(today);
    endDate.setDate(endDate.getDate() + 30);
    
    // Start from the beginning of the current month
    const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
    
    // Day headers
    const dayNames = ['N', 'P', 'U', 'S', 'Č', 'P', 'S'];
    dayNames.forEach(day => {
        const header = document.createElement('div');
        header.className = 'mini-calendar-day header';
        header.textContent = day;
        calendar.appendChild(header);
    });
    
    // Empty cells before month starts
    const firstDay = startDate.getDay();
    const offset = firstDay === 0 ? 6 : firstDay - 1; // Monday = 0
    for (let i = 0; i < offset; i++) {
        const empty = document.createElement('div');
        empty.className = 'mini-calendar-day outside';
        calendar.appendChild(empty);
    }
    
    // Days
    let currentDate = new Date(startDate);
    while (currentDate <= endDate) {
        const dateStr = formatLocalDate(currentDate);
        const day = document.createElement('div');
        day.className = 'mini-calendar-day';
        day.textContent = currentDate.getDate();
        day.dataset.date = dateStr;
        
        // Mark today
        const todayStr = formatLocalDate(today);
        if (dateStr === todayStr) {
            day.classList.add('today');
        }
        
        // Mark reserved dates
        if (unavailableDates.includes(dateStr)) {
            day.classList.add('reserved');
        }
        
        // Mark if outside current display range
        if (currentDate < today) {
            day.classList.add('outside');
        }
        
        calendar.appendChild(day);
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// Update mini calendar with selected dates
function updateMiniCalendar(startDateStr, endDateStr) {
    const calendar = document.getElementById('miniCalendar');
    if (!calendar) return;
    
    // Remove all selected classes
    calendar.querySelectorAll('.mini-calendar-day.selected').forEach(el => {
        el.classList.remove('selected');
    });
    
    if (!startDateStr || !endDateStr) return;
    
    const start = parseLocalDate(startDateStr);
    const end = parseLocalDate(endDateStr);
    
    let currentDate = new Date(start);
    while (currentDate <= end) {
        const dateStr = formatLocalDate(currentDate);
        const dayEl = calendar.querySelector(`[data-date="${dateStr}"]`);
        if (dayEl && !dayEl.classList.contains('reserved')) {
            dayEl.classList.add('selected');
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// Initialize mini calendar
generateMiniCalendar();

document.getElementById('date_start')?.addEventListener('change', function() {
    this.classList.add('has-selection');
    calculatePrice();
    updateMiniCalendar(this.value, document.getElementById('date_end').value);
});
document.getElementById('date_end')?.addEventListener('change', function() {
    this.classList.add('has-selection');
    calculatePrice();
    updateMiniCalendar(document.getElementById('date_start').value, this.value);
});

function calculatePrice() {
    const startInput = document.getElementById('date_start');
    const endInput = document.getElementById('date_end');
    const calcDiv = document.getElementById('priceCalculation');
    const addBtn = document.getElementById('addToCartBtn');
    
    if (!startInput.value || !endInput.value) {
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        addBtn.setAttribute('aria-disabled', 'true');
        return;
    }

    const start = new Date(startInput.value);
    const end = new Date(endInput.value);

    if (end < start) {
        alert('Datum završetka mora biti posle datuma početka.');
        endInput.value = '';
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        addBtn.setAttribute('aria-disabled', 'true');
        return;
    }

    // Calculate days
    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

    if (days > maxDays) {
        alert('Maksimalan period iznajmljivanja je ' + maxDays + ' dana.');
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        addBtn.setAttribute('aria-disabled', 'true');
        return;
    }

    // Check for unavailable dates
    let currentDate = new Date(start);
    let unavailableFound = [];
    while (currentDate <= end) {
        const dateStr = currentDate.toISOString().split('T')[0];
        if (unavailableDates.includes(dateStr)) {
            unavailableFound.push(dateStr);
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }

    if (unavailableFound.length > 0) {
        const dateList = unavailableFound.join(', ');
        alert('Sledeći datumi su već rezervisani i nisu dostupni: ' + dateList + '\n\nMolimo izaberite drugi period.');
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        addBtn.setAttribute('aria-disabled', 'true');
        // Reset selection styling
        startInput.classList.remove('has-selection');
        endInput.classList.remove('has-selection');
        startInput.value = '';
        endInput.value = '';
        updateMiniCalendar(null, null);
        return;
    }
    
    // Count weekend days
    let regularDays = 0;
    let weekendDays = 0;
    currentDate = new Date(start);
    while (currentDate <= end) {
        const day = currentDate.getDay();
        if (day === 0 || day === 6) {
            weekendDays++;
        } else {
            regularDays++;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
    
    // Calculate totals
    const regularTotal = regularDays * toolPrice;
    const weekendTotal = weekendDays * weekendPrice;
    let subtotal = regularTotal + weekendTotal;
    let discount = 0;
    
    if (days >= 7) {
        discount = subtotal * weeklyDiscount;
    }
    
    const total = subtotal - discount;
    
    // Update display
    document.getElementById('calcDays').textContent = days;
    document.getElementById('calcRegular').textContent = regularDays + ' × ' + toolPrice.toFixed(2) + ' € = ' + regularTotal.toFixed(2) + ' €';
    document.getElementById('calcWeekend').textContent = weekendDays + ' × ' + weekendPrice.toFixed(2) + ' € = ' + weekendTotal.toFixed(2) + ' €';
    
    const discountRow = document.getElementById('calcDiscountRow');
    if (discount > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('calcDiscount').textContent = '-' + discount.toFixed(2) + ' €';
    } else {
        discountRow.style.display = 'none';
    }
    
    document.getElementById('calcTotal').textContent = total.toFixed(2) + ' €';
    
    calcDiv.style.display = 'block';
    addBtn.disabled = false;
    addBtn.setAttribute('aria-disabled', 'false');
}

// Add to cart
document.getElementById('addToCartBtn')?.addEventListener('click', function() {
    const start = document.getElementById('date_start').value;
    const end = document.getElementById('date_end').value;
    
    // Send to cart via AJAX
    fetch('<?= url('api/cart') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            tool_id: toolId,
            date_start: start,
            date_end: end
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to cart page immediately
            window.location.href = '<?= url('korpa') ?>';
        } else {
            alert(data.error || 'Greška pri dodavanju u korpu.');
        }
    })
    .catch(error => {
        alert('Greška: ' + error.message);
    });
});
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
