<?php
/**
 * Checkout Page
 */

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    flash('error', 'Vaša korpa je prazna.');
    redirect('korpa');
}

$cart = $_SESSION['cart'];
$errors = [];

// Calculate cart totals
$cartItems = [];
$subtotal = 0;

foreach ($cart as $item) {
    $dates = getDatesBetween($item['date_start'], $item['date_end']);
    $priceInfo = calculateRentalPrice($item['price_24h'], $dates);
    
    $cartItems[] = [
        'tool_id' => $item['tool_id'],
        'tool_name' => $item['tool_name'],
        'price_24h' => $item['price_24h'],
        'date_start' => $item['date_start'],
        'date_end' => $item['date_end'],
        'total_days' => $priceInfo['total_days'],
        'subtotal' => $priceInfo['total']
    ];
    
    $subtotal += $priceInfo['total'];
}

// Delivery options
$deliveryOptions = [
    'pickup' => ['name' => 'Lično preuzimanje', 'price' => DELIVERY_PICKUP],
    'delivery' => ['name' => 'Dostava', 'price' => DELIVERY_ONEWAY],
    'roundtrip' => ['name' => 'Dostava + preuzimanje', 'price' => DELIVERY_ROUNDTRIP]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Nevažeći zahtev. Osvežite stranicu i pokušajte ponovo.';
    } else {
        // Get form data
        $customerName = trim(post('customer_name'));
        $customerEmail = trim(post('customer_email'));
        $customerPhone = trim(post('customer_phone'));
        $customerAddress = trim(post('customer_address'));
        $customerNote = trim(post('customer_note'));
        $deliveryOption = post('delivery_option', 'pickup');
        
        // Validate
        if (empty($customerName)) {
            $errors[] = 'Ime i prezime je obavezno.';
        }
        if (empty($customerEmail)) {
            $errors[] = 'Email adresa je obavezna.';
        } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email adresa nije validna.';
        }
        if (empty($customerPhone)) {
            $errors[] = 'Broj telefona je obavezan.';
        }
        if ($deliveryOption !== 'pickup' && empty($customerAddress)) {
            $errors[] = 'Adresa je obavezna za dostavu.';
        }
        if (!isset($deliveryOptions[$deliveryOption])) {
            $errors[] = 'Nevažeća opcija dostave.';
        }
        
        // Validate tool availability
        foreach ($cart as $item) {
            $tool = db()->fetch("SELECT status FROM tools WHERE id = ?", [$item['tool_id']]);
            if (!$tool || $tool['status'] !== 'available') {
                $errors[] = 'Alat "' . $item['tool_name'] . '" više nije dostupan.';
            }
            
            // Check for conflicting reservations
            $conflict = db()->fetch("
                SELECT r.id FROM reservations r
                JOIN reservation_items ri ON r.id = ri.reservation_id
                WHERE ri.tool_id = ?
                AND r.status IN ('pending', 'confirmed')
                AND (
                    (r.date_start <= ? AND r.date_end >= ?)
                    OR (r.date_start <= ? AND r.date_end >= ?)
                    OR (r.date_start >= ? AND r.date_end <= ?)
                )
            ", [
                $item['tool_id'],
                $item['date_start'], $item['date_start'],
                $item['date_end'], $item['date_end'],
                $item['date_start'], $item['date_end']
            ]);
            
            if ($conflict) {
                $errors[] = 'Alat "' . $item['tool_name'] . '" je već rezervisan za odabrane datume.';
            }
        }
        
        if (empty($errors)) {
            // Calculate final totals
            $deliveryFee = $deliveryOptions[$deliveryOption]['price'];
            $total = $subtotal + $deliveryFee;
            
            // Get date range (earliest start, latest end)
            $dateStart = min(array_column($cartItems, 'date_start'));
            $dateEnd = max(array_column($cartItems, 'date_end'));
            $totalDays = count(getDatesBetween($dateStart, $dateEnd));
            
            // Generate reservation code
            $reservationCode = generateReservationCode();
            
            db()->beginTransaction();
            
            try {
                // Create reservation
                $reservationId = db()->insert("
                    INSERT INTO reservations (
                        reservation_code, status,
                        customer_name, customer_email, customer_phone, customer_address, customer_note,
                        date_start, date_end, total_days,
                        subtotal, weekend_markup, discount, delivery_option, delivery_fee, total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $reservationCode, 'pending',
                    $customerName, $customerEmail, $customerPhone, $customerAddress, $customerNote,
                    $dateStart, $dateEnd, $totalDays,
                    $subtotal, 0, 0, $deliveryOption, $deliveryFee, $total
                ]);
                
                // Create reservation items
                foreach ($cartItems as $item) {
                    db()->insert("
                        INSERT INTO reservation_items (reservation_id, tool_id, tool_name, price_per_day, days, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [
                        $reservationId,
                        $item['tool_id'],
                        $item['tool_name'],
                        $item['price_24h'],
                        $item['total_days'],
                        $item['subtotal']
                    ]);
                }
                
                db()->commit();
                
                // Send Telegram notification
                $reservationData = [
                    'code' => $reservationCode,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'delivery_option' => $deliveryOption,
                    'delivery_address' => $customerAddress,
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                    'total_price' => $total,
                    'deposit_total' => 0,
                    'notes' => $customerNote
                ];
                
                $telegramItems = [];
                foreach ($cartItems as $item) {
                    $telegramItems[] = [
                        'tool_name' => $item['tool_name'],
                        'price' => $item['subtotal']
                    ];
                }
                
                $telegramMessage = formatReservationTelegramMessage($reservationData, $telegramItems);
                sendTelegramNotification($telegramMessage);
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Store reservation code for thank you page
                $_SESSION['last_reservation'] = $reservationCode;
                
                redirect('zahvalnica');
                
            } catch (Exception $e) {
                db()->rollback();
                $errors[] = 'Greška pri kreiranju rezervacije. Pokušajte ponovo.';
            }
        }
    }
}

$pageTitle = 'Checkout - ' . SITE_NAME;
$showSidebar = false;

ob_start();
?>

<div class="checkout-page">
    <h1>Checkout</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="checkout-form">
        <?= csrfField() ?>
        
        <div class="checkout-grid">
            
            <!-- Customer Info -->
            <div class="checkout-section">
                <h2>Vaši podaci</h2>
                
                <div class="form-group">
                    <label for="customer_name" class="form-label required">Ime i prezime</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" 
                           value="<?= e(post('customer_name')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_email" class="form-label required">Email adresa</label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control" 
                           value="<?= e(post('customer_email')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone" class="form-label required">Broj telefona</label>
                    <input type="tel" id="customer_phone" name="customer_phone" class="form-control" 
                           value="<?= e(post('customer_phone')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_address" class="form-label" id="addressLabel">Adresa</label>
                    <textarea id="customer_address" name="customer_address" class="form-control" rows="2"><?= e(post('customer_address')) ?></textarea>
                    <p class="form-text">Obavezno za dostavu</p>
                </div>
                
                <div class="form-group">
                    <label for="customer_note" class="form-label">Napomena</label>
                    <textarea id="customer_note" name="customer_note" class="form-control" rows="2" 
                              placeholder="Dodatne napomene (opciono)"><?= e(post('customer_note')) ?></textarea>
                </div>
            </div>
            
            <!-- Delivery Options -->
            <div class="checkout-section">
                <h2>Način preuzimanja</h2>
                
                <div class="delivery-options">
                    <?php foreach ($deliveryOptions as $key => $option): ?>
                    <label class="delivery-option">
                        <input type="radio" name="delivery_option" value="<?= $key ?>" 
                               <?= (post('delivery_option', 'pickup') === $key) ? 'checked' : '' ?>
                               onchange="updateDelivery()">
                        <span class="option-content">
                            <span class="option-name"><?= e($option['name']) ?></span>
                            <span class="option-price">
                                <?= $option['price'] > 0 ? formatPrice($option['price']) : 'Besplatno' ?>
                            </span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="info-box mt-3">
                    <p><strong>Plaćanje:</strong> Gotovina prilikom preuzimanja/dostave</p>
                    <p><strong>Depozit:</strong> Može biti potreban za određene alate</p>
                </div>
            </div>
            
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Pregled narudžbe</h2>
            
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Alat</th>
                        <th>Period</th>
                        <th>Dana</th>
                        <th class="text-right">Cena</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= e($item['tool_name']) ?></td>
                        <td><?= formatDate($item['date_start']) ?> - <?= formatDate($item['date_end']) ?></td>
                        <td><?= $item['total_days'] ?></td>
                        <td class="text-right"><?= formatPrice($item['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Alati ukupno:</strong></td>
                        <td class="text-right"><strong><?= formatPrice($subtotal) ?></strong></td>
                    </tr>
                    <tr id="deliveryRow">
                        <td colspan="3">Dostava:</td>
                        <td class="text-right" id="deliveryPrice">Besplatno</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3"><strong>UKUPNO:</strong></td>
                        <td class="text-right"><strong id="grandTotal"><?= formatPrice($subtotal) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="checkout-actions">
            <a href="<?= url('korpa') ?>" class="btn btn-secondary">← Nazad na korpu</a>
            <button type="submit" class="btn btn-primary btn-large">Potvrdi rezervaciju</button>
        </div>
        
    </form>
</div>

<style>
.checkout-page {
    max-width: 1000px;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xl);
    margin: var(--spacing-xl) 0;
}

@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}

.checkout-section {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.checkout-section h2 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-accent);
}

.delivery-options {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.delivery-option {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.2s;
}

.delivery-option:hover {
    border-color: var(--color-gray-400);
}

.delivery-option input:checked + .option-content {
    color: var(--color-black);
}

.delivery-option input:checked ~ .option-content,
.delivery-option:has(input:checked) {
    border-color: var(--color-accent);
    background: var(--color-accent-light);
}

.option-content {
    display: flex;
    justify-content: space-between;
    width: 100%;
}

.option-name {
    font-weight: 500;
}

.option-price {
    font-weight: 700;
}

.info-box {
    background: var(--color-gray-100);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    font-size: var(--font-size-small);
}

.info-box p {
    margin: var(--spacing-xs) 0;
}

.order-summary {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
}

.order-summary h2 {
    font-size: var(--font-size-large);
    margin-bottom: var(--spacing-lg);
}

.summary-table {
    width: 100%;
}

.summary-table th,
.summary-table td {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.summary-table th {
    text-align: left;
    font-weight: 600;
    background: var(--color-gray-100);
}

.summary-table tfoot td {
    padding-top: var(--spacing-md);
}

.summary-table .total-row {
    font-size: 1.2em;
}

.summary-table .total-row td {
    border-top: 2px solid var(--color-accent);
    padding-top: var(--spacing-md);
}

.checkout-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .checkout-actions {
        flex-direction: column;
    }
    
    .checkout-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
const subtotal = <?= $subtotal ?>;
const deliveryPrices = <?= json_encode(array_map(fn($o) => $o['price'], $deliveryOptions)) ?>;

function updateDelivery() {
    const selected = document.querySelector('input[name="delivery_option"]:checked').value;
    const deliveryPrice = deliveryPrices[selected] || 0;
    const total = subtotal + deliveryPrice;
    
    document.getElementById('deliveryPrice').textContent = deliveryPrice > 0 
        ? deliveryPrice.toFixed(2) + ' €' 
        : 'Besplatno';
    document.getElementById('grandTotal').textContent = total.toFixed(2) + ' €';
    
    // Update address requirement
    const addressLabel = document.getElementById('addressLabel');
    if (selected !== 'pickup') {
        addressLabel.classList.add('required');
    } else {
        addressLabel.classList.remove('required');
    }
}

// Initial update
updateDelivery();
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
