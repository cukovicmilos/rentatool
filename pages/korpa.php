<?php
/**
 * Cart Page
 */

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle remove action
if (get('action') === 'remove' && isset($_GET['index'])) {
    $index = (int) $_GET['index'];
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
        flash('success', 'Stavka je uklonjena iz korpe.');
    }
    redirect('korpa');
}

// Handle clear action
if (get('action') === 'clear') {
    $_SESSION['cart'] = [];
    flash('info', 'Korpa je ispražnjena.');
    redirect('korpa');
}

$cart = $_SESSION['cart'];

// Calculate totals for each item and grand total
$grandTotal = 0;
$cartItems = [];
$hasServices = false;

foreach ($cart as $index => $item) {
    if (isset($item['type']) && $item['type'] === 'service') {
        $hasServices = true;
        $cartItems[] = [
            'index' => $index,
            'type' => 'service',
            'service_label' => $item['service_label'],
            'description' => $item['description'],
            'service_date' => $item['service_date'],
            'location' => $item['location'],
            'total' => 0
        ];
    } else {
        $dates = getDatesBetween($item['date_start'], $item['date_end']);
        $priceInfo = calculateRentalPrice($item['price_24h'], $dates);
        
        $cartItems[] = [
            'index' => $index,
            'type' => 'tool',
            'tool_id' => $item['tool_id'],
            'tool_name' => $item['tool_name'],
            'tool_slug' => $item['tool_slug'],
            'price_24h' => $item['price_24h'],
            'date_start' => $item['date_start'],
            'date_end' => $item['date_end'],
            'total_days' => $priceInfo['total_days'],
            'regular_days' => $priceInfo['regular_days'],
            'weekend_days' => $priceInfo['weekend_days'],
            'subtotal' => $priceInfo['subtotal'],
            'discount' => $priceInfo['discount'],
            'total' => $priceInfo['total']
        ];
        
        $grandTotal += $priceInfo['total'];
    }
}

$pageTitle = 'Korpa - ' . SITE_NAME;
$showSidebar = false;

ob_start();
?>

<div class="cart-page">
    <h1>Korpa</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            Vaša korpa je prazna.
        </div>
        <p><a href="<?= url('') ?>" class="btn btn-primary">← Pregledajte alate</a></p>
    <?php else: ?>
        
        <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
            <?php if ($item['type'] === 'service'): ?>
            <div class="cart-item cart-item-service">
                <div class="cart-item-info">
                    <h3><?= e($item['service_label']) ?></h3>
                    <p class="cart-item-description"><?= nl2br(e($item['description'])) ?></p>
                    <p class="cart-item-dates">
                        <strong>Željeni datum:</strong> <?= formatDate($item['service_date']) ?>
                    </p>
                    <p class="cart-item-location">
                        <strong>Lokacija:</strong> <?= $item['location'] === 'workshop' ? 'Doneti u radionicu' : 'Dolazim kod vas' ?>
                    </p>
                    <p class="cart-item-price-note"><em>Cena se dogovara unapred</em></p>
                </div>
                <div class="cart-item-price">
                    <span class="item-total item-total-service">Dogovor</span>
                    <a href="<?= url('korpa?action=remove&index=' . $item['index']) ?>" 
                       class="btn btn-danger btn-small"
                       onclick="return confirm('Ukloniti ovu stavku?')">Ukloni</a>
                </div>
            </div>
            <?php else: ?>
            <div class="cart-item">
                <div class="cart-item-info">
                    <h3>
                        <a href="<?= url('alat/' . $item['tool_slug']) ?>"><?= e($item['tool_name']) ?></a>
                    </h3>
                    <p class="cart-item-dates">
                        <strong>Period:</strong> 
                        <?= formatDate($item['date_start']) ?> - <?= formatDate($item['date_end']) ?>
                        (<?= $item['total_days'] ?> <?= $item['total_days'] == 1 ? 'dan' : 'dana' ?>)
                    </p>
                    <p class="cart-item-breakdown">
                        <?php if ($item['regular_days'] > 0): ?>
                            <?= $item['regular_days'] ?> radnih dana × <?= formatPrice($item['price_24h']) ?>
                        <?php endif; ?>
                        <?php if ($item['weekend_days'] > 0): ?>
                            <?php if ($item['regular_days'] > 0): ?> + <?php endif; ?>
                            <?= $item['weekend_days'] ?> vikend dana × <?= formatPrice($item['price_24h'] * (1 + WEEKEND_MARKUP)) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($item['discount'] > 0): ?>
                    <p class="cart-item-discount text-success">
                        Popust (7+ dana): -<?= formatPrice($item['discount']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="cart-item-price">
                    <span class="item-total"><?= formatPrice($item['total']) ?></span>
                    <a href="<?= url('korpa?action=remove&index=' . $item['index']) ?>" 
                       class="btn btn-danger btn-small"
                       onclick="return confirm('Ukloniti ovu stavku?')">Ukloni</a>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-summary">
            <?php if ($hasServices): ?>
            <div class="summary-row">
                <span>Alati ukupno:</span>
                <span><?= formatPrice($grandTotal) ?></span>
            </div>
            <div class="summary-row">
                <span>Usluge:</span>
                <span><em>Dogovor</em></span>
            </div>
            <?php endif; ?>
            <div class="summary-row summary-total">
                <span><?= $hasServices ? 'Ukupno za alate:' : 'Ukupno:' ?></span>
                <span><?= formatPrice($grandTotal) ?></span>
            </div>
            <p class="text-muted">
                <small>* Dostava i usluge se dogovaraju na sledećem koraku</small>
            </p>
        </div>
        
        <div class="cart-actions">
            <a href="<?= url('') ?>" class="btn btn-secondary">← Nastavi kupovinu</a>
            <a href="<?= url('korpa?action=clear') ?>" class="btn btn-secondary" 
               onclick="return confirm('Isprazni celu korpu?')">Isprazni korpu</a>
            <a href="<?= url('checkout') ?>" class="btn btn-primary btn-large">Unesi svoje podatke →</a>
        </div>
        
    <?php endif; ?>
</div>

<style>
.cart-page {
    max-width: 800px;
}

.cart-items {
    margin: var(--spacing-xl) 0;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-lg);
    background: var(--color-white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-md);
}

.cart-item-info h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-large);
}

.cart-item-info h3 a {
    color: var(--color-black);
}

.cart-item-info h3 a:hover {
    color: var(--color-accent-hover);
}

.cart-item-dates,
.cart-item-breakdown {
    margin: 0;
    font-size: var(--font-size-small);
    color: var(--color-gray-600);
}

.cart-item-discount {
    margin: var(--spacing-xs) 0 0 0;
    font-size: var(--font-size-small);
}

.cart-item-service {
    border-left: 4px solid var(--color-accent);
}

.cart-item-description {
    margin: var(--spacing-xs) 0;
    font-size: var(--font-size-small);
    color: var(--color-gray-600);
    background: var(--color-gray-100);
    padding: var(--spacing-sm);
    border-radius: var(--border-radius);
}

.cart-item-location {
    margin: 0;
    font-size: var(--font-size-small);
    color: var(--color-gray-600);
}

.cart-item-price-note {
    margin: var(--spacing-xs) 0 0 0;
    font-size: var(--font-size-small);
    color: var(--color-gray-500);
}

.item-total-service {
    color: var(--color-gray-600);
}

.cart-item-price {
    text-align: right;
    flex-shrink: 0;
    margin-left: var(--spacing-lg);
}

.item-total {
    display: block;
    font-size: var(--font-size-large);
    font-weight: 700;
    margin-bottom: var(--spacing-sm);
}

.cart-summary {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
}

.summary-total {
    font-size: 1.3em;
    font-weight: 700;
    border-top: 2px solid var(--color-accent);
    padding-top: var(--spacing-md);
}

.cart-actions {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .cart-item {
        flex-direction: column;
    }
    
    .cart-item-price {
        margin-left: 0;
        margin-top: var(--spacing-md);
        text-align: left;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .cart-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
