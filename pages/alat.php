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
            <div class="gallery-main">
                <img src="<?= upload('tools/' . $images[0]['filename']) ?>" 
                     alt="<?= e($tool['name']) ?>" 
                     id="mainImage">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" 
                     onclick="changeImage('<?= upload('tools/' . $img['filename']) ?>', this)">
                    <img src="<?= upload('tools/' . $img['filename']) ?>" alt="">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="gallery-main no-image">
                <span>Nema slike</span>
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
            <div class="date-selection mt-3">
                <h3>Izaberite period</h3>
                <div class="date-inputs">
                    <div class="form-group">
                        <label for="date_start" class="form-label">Od:</label>
                        <input type="date" id="date_start" class="form-control" 
                               min="<?= date('Y-m-d') ?>" 
                               max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_end" class="form-label">Do:</label>
                        <input type="date" id="date_end" class="form-control"
                               min="<?= date('Y-m-d') ?>" 
                               max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
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
                
                <button type="button" id="addToCartBtn" class="btn btn-primary btn-large btn-block mt-2" disabled>
                    Dodaj u korpu
                </button>
                <p class="text-muted text-center mt-1">
                    <small>Max <?= MAX_RENTAL_DAYS ?> dana, rezervacija do <?= MAX_ADVANCE_DAYS ?> dana unapred</small>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($categories)): ?>
            <div class="tool-categories mt-3">
                <strong>Kategorije:</strong>
                <?php foreach ($categories as $cat): ?>
                <a href="<?= url('kategorija/' . $cat['slug']) ?>" class="btn btn-secondary btn-small"><?= e($cat['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Description & Specs -->
    <div class="tool-details-section mt-4">
        <?php if ($tool['description']): ?>
        <div class="tool-description">
            <h2>Opis</h2>
            <div class="description-content">
                <?= nl2br(e($tool['description'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
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
    background: var(--color-gray-100);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: contain;
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

.date-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
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
</style>

<script>
// Gallery
function changeImage(src, thumb) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// Date calculation
const toolPrice = <?= $tool['price_24h'] ?>;
const weekendPrice = <?= $weekendPrice ?>;
const weekendMarkup = <?= WEEKEND_MARKUP ?>;
const weeklyDiscount = <?= WEEKLY_DISCOUNT ?>;
const maxDays = <?= MAX_RENTAL_DAYS ?>;
const unavailableDates = <?= json_encode($unavailableDates) ?>;
const toolId = <?= $tool['id'] ?>;
const toolName = <?= json_encode($tool['name']) ?>;

document.getElementById('date_start')?.addEventListener('change', calculatePrice);
document.getElementById('date_end')?.addEventListener('change', calculatePrice);

function calculatePrice() {
    const startInput = document.getElementById('date_start');
    const endInput = document.getElementById('date_end');
    const calcDiv = document.getElementById('priceCalculation');
    const addBtn = document.getElementById('addToCartBtn');
    
    if (!startInput.value || !endInput.value) {
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        return;
    }
    
    const start = new Date(startInput.value);
    const end = new Date(endInput.value);
    
    if (end < start) {
        alert('Datum završetka mora biti posle datuma početka.');
        endInput.value = '';
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        return;
    }
    
    // Calculate days
    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
    
    if (days > maxDays) {
        alert('Maksimalan period iznajmljivanja je ' + maxDays + ' dana.');
        calcDiv.style.display = 'none';
        addBtn.disabled = true;
        return;
    }
    
    // Check for unavailable dates
    let currentDate = new Date(start);
    while (currentDate <= end) {
        const dateStr = currentDate.toISOString().split('T')[0];
        if (unavailableDates.includes(dateStr)) {
            alert('Datum ' + dateStr + ' nije dostupan. Molimo izaberite drugi period.');
            calcDiv.style.display = 'none';
            addBtn.disabled = true;
            return;
        }
        currentDate.setDate(currentDate.getDate() + 1);
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
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            } else {
                const cartLink = document.querySelector('.cart-link');
                if (cartLink) {
                    const span = document.createElement('span');
                    span.className = 'cart-count';
                    span.textContent = data.cart_count;
                    cartLink.appendChild(span);
                }
            }
            alert('Alat je dodat u korpu!');
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
