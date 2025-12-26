<?php
/**
 * Reservation View Page
 */

$code = get('code', '');

if (empty($code)) {
    redirect('');
}

// Get reservation
$reservation = db()->fetch("SELECT * FROM reservations WHERE reservation_code = ?", [$code]);

if (!$reservation) {
    $pageTitle = 'Rezervacija nije pronađena';
    $content = '<div class="alert alert-error">Rezervacija sa ovim kodom nije pronađena.</div><p><a href="' . url('') . '">← Nazad na početnu</a></p>';
    $showSidebar = false;
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// Get reservation items
$items = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$reservation['id']]);

// Check if can be cancelled (min 2 days before start)
$canCancel = false;
$cancelMessage = '';
if ($reservation['status'] === 'pending' || $reservation['status'] === 'confirmed') {
    $daysUntilStart = (strtotime($reservation['date_start']) - strtotime('today')) / 86400;
    if ($daysUntilStart >= MIN_CANCEL_DAYS) {
        $canCancel = true;
    } else {
        $cancelMessage = 'Otkazivanje nije moguće manje od ' . MIN_CANCEL_DAYS . ' dana pre početka rezervacije.';
    }
}

// Status names
$statusNames = [
    'pending' => 'Na čekanju',
    'confirmed' => 'Potvrđena',
    'completed' => 'Završena',
    'cancelled' => 'Otkazana'
];

// Delivery names
$deliveryNames = [
    'pickup' => 'Lično preuzimanje',
    'delivery' => 'Dostava',
    'roundtrip' => 'Dostava + preuzimanje'
];

$pageTitle = 'Rezervacija ' . $reservation['reservation_code'] . ' - ' . SITE_NAME;
$showSidebar = false;

ob_start();
?>

<div class="reservation-page">
    
    <h1>Rezervacija #<?= e($reservation['reservation_code']) ?></h1>
    
    <div class="status-banner status-<?= $reservation['status'] ?>">
        Status: <strong><?= $statusNames[$reservation['status']] ?? $reservation['status'] ?></strong>
    </div>
    
    <div class="reservation-details">
        
        <div class="details-grid">
            <div class="detail-section">
                <h3>Podaci o kupcu</h3>
                <p><strong>Ime:</strong> <?= e($reservation['customer_name']) ?></p>
                <p><strong>Email:</strong> <?= e($reservation['customer_email']) ?></p>
                <p><strong>Telefon:</strong> <?= e($reservation['customer_phone']) ?></p>
                <?php if ($reservation['customer_address']): ?>
                <p><strong>Adresa:</strong> <?= e($reservation['customer_address']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="detail-section">
                <h3>Detalji</h3>
                <p><strong>Period:</strong> <?= formatDate($reservation['date_start']) ?> - <?= formatDate($reservation['date_end']) ?></p>
                <p><strong>Preuzimanje:</strong> <?= $deliveryNames[$reservation['delivery_option']] ?? $reservation['delivery_option'] ?></p>
                <p><strong>Kreirana:</strong> <?= formatDateTime($reservation['created_at']) ?></p>
            </div>
        </div>
        
        <div class="items-section">
            <h3>Rezervisani alati</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Alat</th>
                        <th>Cena/dan</th>
                        <th>Dana</th>
                        <th class="text-right">Ukupno</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['tool_name']) ?></td>
                        <td><?= formatPrice($item['price_per_day']) ?></td>
                        <td><?= $item['days'] ?></td>
                        <td class="text-right"><?= formatPrice($item['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Alati:</td>
                        <td class="text-right"><?= formatPrice($reservation['subtotal']) ?></td>
                    </tr>
                    <?php if ($reservation['delivery_fee'] > 0): ?>
                    <tr>
                        <td colspan="3">Dostava:</td>
                        <td class="text-right"><?= formatPrice($reservation['delivery_fee']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3"><strong>UKUPNO:</strong></td>
                        <td class="text-right"><strong><?= formatPrice($reservation['total']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
    </div>
    
    <?php if ($canCancel): ?>
    <div class="cancel-section">
        <h3>Otkazivanje rezervacije</h3>
        <p>Ako želite da otkažete ovu rezervaciju, kliknite na dugme ispod.</p>
        <a href="<?= url('rezervacija/otkazi/' . $reservation['reservation_code']) ?>" 
           class="btn btn-danger"
           onclick="return confirm('Da li ste sigurni da želite da otkažete ovu rezervaciju?')">
            Otkaži rezervaciju
        </a>
    </div>
    <?php elseif ($cancelMessage): ?>
    <div class="cancel-section">
        <p class="text-muted"><?= e($cancelMessage) ?></p>
    </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="<?= url('') ?>" class="btn btn-secondary">← Nazad na početnu</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Štampaj</button>
    </div>
    
</div>

<style>
.reservation-page {
    max-width: 800px;
}

.status-banner {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
    text-align: center;
    font-size: var(--font-size-large);
}

.status-banner.status-pending {
    background: #FFF3CD;
    color: #856404;
}

.status-banner.status-confirmed {
    background: #CCE5FF;
    color: #004085;
}

.status-banner.status-completed {
    background: #D4EDDA;
    color: #155724;
}

.status-banner.status-cancelled {
    background: #F8D7DA;
    color: #721C24;
}

.reservation-details {
    background: var(--color-white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
}

.detail-section h3,
.items-section h3 {
    font-size: var(--font-size-base);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-xs);
    border-bottom: 2px solid var(--color-accent);
}

.detail-section p {
    margin: var(--spacing-xs) 0;
    font-size: var(--font-size-small);
}

.items-table {
    width: 100%;
}

.items-table th,
.items-table td {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.items-table th {
    background: var(--color-gray-100);
    text-align: left;
}

.items-table .total-row td {
    border-top: 2px solid var(--color-accent);
}

.cancel-section {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
}

.cancel-section h3 {
    margin-top: 0;
}

.action-buttons {
    display: flex;
    gap: var(--spacing-md);
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
