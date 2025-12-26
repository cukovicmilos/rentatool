<?php
/**
 * Thank You Page - Order Confirmation
 */

// Check for reservation code
$reservationCode = $_SESSION['last_reservation'] ?? get('code', '');

if (empty($reservationCode)) {
    redirect('');
}

// Get reservation
$reservation = db()->fetch("SELECT * FROM reservations WHERE reservation_code = ?", [$reservationCode]);

if (!$reservation) {
    flash('error', 'Rezervacija nije pronađena.');
    redirect('');
}

// Get reservation items
$items = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$reservation['id']]);

// Clear session reservation code
unset($_SESSION['last_reservation']);

// Delivery options names
$deliveryNames = [
    'pickup' => 'Lično preuzimanje',
    'delivery' => 'Dostava',
    'roundtrip' => 'Dostava + preuzimanje'
];

$pageTitle = 'Rezervacija potvrđena - ' . SITE_NAME;
$showSidebar = false;

ob_start();
?>

<div class="thankyou-page">
    
    <div class="success-message">
        <div class="success-icon">✓</div>
        <h1>Hvala na rezervaciji!</h1>
        <p>Vaša rezervacija je uspešno kreirana.</p>
    </div>
    
    <div class="reservation-code-box">
        <span class="label">Broj rezervacije:</span>
        <span class="code"><?= e($reservation['reservation_code']) ?></span>
    </div>
    
    <div class="confirmation-details" id="printArea">
        
        <div class="print-header">
            <h2>🔧 <?= SITE_NAME ?></h2>
            <p>Potvrda rezervacije</p>
        </div>
        
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
                <h3>Detalji rezervacije</h3>
                <p><strong>Broj:</strong> <?= e($reservation['reservation_code']) ?></p>
                <p><strong>Datum:</strong> <?= formatDateTime($reservation['created_at']) ?></p>
                <p><strong>Period:</strong> <?= formatDate($reservation['date_start']) ?> - <?= formatDate($reservation['date_end']) ?></p>
                <p><strong>Preuzimanje:</strong> <?= e($deliveryNames[$reservation['delivery_option']] ?? $reservation['delivery_option']) ?></p>
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
                        <td colspan="3">Alati ukupno:</td>
                        <td class="text-right"><?= formatPrice($reservation['subtotal']) ?></td>
                    </tr>
                    <?php if ($reservation['delivery_fee'] > 0): ?>
                    <tr>
                        <td colspan="3">Dostava:</td>
                        <td class="text-right"><?= formatPrice($reservation['delivery_fee']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3"><strong>UKUPNO ZA PLATITI:</strong></td>
                        <td class="text-right"><strong><?= formatPrice($reservation['total']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="payment-info">
            <h3>Informacije o plaćanju</h3>
            <p>Plaćanje se vrši <strong>gotovinom</strong> prilikom preuzimanja alata.</p>
            <p>Za određene alate može biti potreban depozit (kaucija) koji se vraća po vraćanju alata.</p>
        </div>
        
        <div class="contact-info">
            <h3>Kontakt</h3>
            <p>Telefon: <?= SITE_PHONE ?></p>
            <p>Email: <?= SITE_EMAIL ?></p>
        </div>
        
    </div>
    
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-secondary btn-large">
            🖨️ Štampaj potvrdu
        </button>
        <a href="<?= url('') ?>" class="btn btn-primary btn-large">
            ← Nazad na početnu
        </a>
    </div>
    
    <div class="next-steps">
        <h3>Šta dalje?</h3>
        <ul>
            <li>Sačuvajte broj rezervacije: <strong><?= e($reservation['reservation_code']) ?></strong></li>
            <li>Kontaktiraćemo vas radi potvrde termina</li>
            <li>
                <?php if ($reservation['delivery_option'] === 'pickup'): ?>
                Dođite po alat na dogovoreni datum
                <?php else: ?>
                Alat će biti dostavljen na vašu adresu
                <?php endif; ?>
            </li>
            <li>Pripremite gotovinu za plaćanje (<?= formatPrice($reservation['total']) ?>)</li>
            <li>
                <a href="<?= url('rezervacija/' . $reservation['reservation_code']) ?>">Pogledajte ili otkažite rezervaciju</a>
            </li>
        </ul>
    </div>
    
</div>

<style>
.thankyou-page {
    max-width: 800px;
    margin: 0 auto;
}

.success-message {
    text-align: center;
    padding: var(--spacing-xl);
    background: #D4EDDA;
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
}

.success-icon {
    width: 60px;
    height: 60px;
    background: var(--color-success);
    color: white;
    font-size: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-md);
}

.success-message h1 {
    margin: 0 0 var(--spacing-sm) 0;
    color: #155724;
}

.success-message p {
    margin: 0;
    color: #155724;
}

.reservation-code-box {
    background: var(--color-accent);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    text-align: center;
    margin-bottom: var(--spacing-xl);
}

.reservation-code-box .label {
    display: block;
    font-size: var(--font-size-small);
    margin-bottom: var(--spacing-xs);
}

.reservation-code-box .code {
    font-size: 2em;
    font-weight: 700;
    font-family: monospace;
    letter-spacing: 2px;
}

.confirmation-details {
    background: var(--color-white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.print-header {
    display: none;
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
.items-section h3,
.payment-info h3,
.contact-info h3 {
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
    margin-bottom: var(--spacing-xl);
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
    font-size: 1.1em;
}

.payment-info,
.contact-info {
    background: var(--color-gray-100);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-md);
}

.payment-info p,
.contact-info p {
    margin: var(--spacing-xs) 0;
    font-size: var(--font-size-small);
}

.action-buttons {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
    margin-bottom: var(--spacing-xl);
}

.next-steps {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
}

.next-steps h3 {
    margin-top: 0;
}

.next-steps ul {
    margin: 0;
    padding-left: var(--spacing-lg);
}

.next-steps li {
    margin-bottom: var(--spacing-sm);
    list-style: decimal;
}

/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    #printArea, #printArea * {
        visibility: visible;
    }
    
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
    
    .print-header {
        display: block;
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #000;
    }
    
    .print-header h2 {
        margin: 0;
    }
    
    .action-buttons,
    .next-steps {
        display: none;
    }
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
