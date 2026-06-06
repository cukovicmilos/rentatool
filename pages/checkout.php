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
$weekendMarkupTotal = 0;
$discountTotal = 0;
$hasServices = false;
$serviceDates = [];

foreach ($cart as $item) {
    if (isset($item['type']) && $item['type'] === 'service') {
        $hasServices = true;
        $serviceDates[] = $item['service_date'];
        $cartItems[] = [
            'type' => 'service',
            'service_label' => $item['service_label'],
            'description' => $item['description'],
            'service_date' => $item['service_date'],
            'location' => $item['location'],
            'subtotal' => 0
        ];
    } else {
        $dates = getDatesBetween($item['date_start'], $item['date_end']);
        $timeStart = $item['time_start'] ?? '08:00';
        $timeEnd = $item['time_end'] ?? '18:00';
        $priceInfo = calculateRentalPrice($item['price_24h'], $dates, $item['date_start'], $item['date_end'], $timeStart, $timeEnd);
        
        $itemBase = $item['price_24h'] * $priceInfo['total_days'];
        $itemMarkup = $priceInfo['weekend_days'] * $item['price_24h'] * WEEKEND_MARKUP;
        
        $cartItems[] = [
            'type' => 'tool',
            'tool_id' => $item['tool_id'],
            'tool_name' => $item['tool_name'],
            'price_24h' => $item['price_24h'],
            'date_start' => $item['date_start'],
            'date_end' => $item['date_end'],
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
            'total_days' => $priceInfo['total_days'],
            'total_hours' => $priceInfo['total_hours'],
            'regular_days' => $priceInfo['regular_days'],
            'weekend_days' => $priceInfo['weekend_days'],
            'subtotal' => $itemBase,
            'discount' => $priceInfo['discount']
        ];
        
        $subtotal += $itemBase;
        $weekendMarkupTotal += $itemMarkup;
        $discountTotal += $priceInfo['discount'];
    }
}

// Delivery options
$deliveryOptions = [
    'pickup' => ['name' => 'Dolazim do radionice', 'price' => 0],
    'delivery' => ['name' => 'Izlazak na teren', 'price' => 10]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Nevažeći zahtev. Osvežite stranicu i pokušajte ponovo.';
    } else {
        // Get form data
        $customerName = trim(post('customer_name'));
        $customerEmail = trim(post('customer_email'));
        $phonePrefix = trim(post('phone_prefix'));
        $customerPhone = trim(post('customer_phone'));
        // Strip leading zero from phone number, then prepend prefix
        $customerPhone = $phonePrefix . ltrim($customerPhone, '0');
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
        } elseif (empty($phonePrefix)) {
            $errors[] = 'Izaberite pozivni broj države.';
        } elseif (!preg_match('/^\+\d{1,3}\d{6,12}$/', $customerPhone)) {
            $errors[] = 'Broj telefona nije validan.';
        }
        if (empty($customerAddress)) {
            $errors[] = 'Adresa je obavezna.';
        }
        if (!isset($deliveryOptions[$deliveryOption])) {
            $errors[] = 'Nevažeća opcija dostave.';
        }
        
        // Validate tool availability (skip for service items)
        foreach ($cart as $item) {
            if (isset($item['type']) && $item['type'] === 'service') {
                continue;
            }
            
            $tool = db()->fetch("SELECT status FROM tools WHERE id = ?", [$item['tool_id']]);
            if (!$tool || $tool['status'] !== 'available') {
                $errors[] = 'Alat "' . $item['tool_name'] . '" više nije dostupan.';
            }
            
            // Check for conflicting reservations (datetime overlap)
            $reqTimeStart = $item['time_start'] ?? '08:00';
            $reqTimeEnd = $item['time_end'] ?? '18:00';
            $reqStartDT = $item['date_start'] . ' ' . $reqTimeStart;
            $reqEndDT = $item['date_end'] . ' ' . $reqTimeEnd;
            $reqStartTs = strtotime($reqStartDT);
            $reqEndTs = strtotime($reqEndDT);
            
            $conflicts = db()->fetchAll("
                SELECT r.date_start, r.date_end, r.time_start, r.time_end
                FROM reservations r
                JOIN reservation_items ri ON r.id = ri.reservation_id
                WHERE ri.tool_id = ?
                AND r.status IN ('pending', 'confirmed', 'rented')
                AND r.date_end >= ? AND r.date_start <= ?
            ", [
                $item['tool_id'],
                $item['date_start'], $item['date_end']
            ]);
            
            foreach ($conflicts as $conflict) {
                $cTimeStart = $conflict['time_start'] ?? '08:00';
                $cTimeEnd   = $conflict['time_end'] ?? '18:00';
                $confStartDT = $conflict['date_start'] . ' ' . $cTimeStart;
                $confEndDT = $conflict['date_end'] . ' ' . $cTimeEnd;
                
                if (strtotime($confStartDT) < $reqEndTs && strtotime($confEndDT) > $reqStartTs) {
                    $errors[] = 'Alat "' . $item['tool_name'] . '" je već rezervisan za odabrani termin.';
                    break;
                }
            }
        }
        
        if (empty($errors)) {
            // Calculate final totals
            $deliveryFee = $deliveryOptions[$deliveryOption]['price'];
            $total = $subtotal + $weekendMarkupTotal - $discountTotal + $deliveryFee;
            
            // Get date range - handle both tool dates and service dates
            $toolDates = array_filter(array_column($cartItems, 'date_start'));
            $allDates = array_merge($toolDates, $serviceDates);
            
            // Get time from first tool item
            $timeStart = '08:00';
            $timeEnd = '18:00';
            foreach ($cartItems as $item) {
                if ($item['type'] === 'tool') {
                    $timeStart = $item['time_start'] ?? '08:00';
                    $timeEnd = $item['time_end'] ?? '18:00';
                    break;
                }
            }
            
            if (!empty($allDates)) {
                $dateStart = min($allDates);
                $dateEnd = max($allDates);
                $totalDays = calculateRentalDays($dateStart, $dateEnd, $timeStart, $timeEnd);
            } else {
                // Only services, use current date
                $dateStart = date('Y-m-d');
                $dateEnd = date('Y-m-d');
                $totalDays = 1;
            }
            
            // Generate reservation code
            $reservationCode = generateReservationCode();
            
            db()->beginTransaction();
            
            try {
                // Create reservation
                $reservationId = db()->insert("
                    INSERT INTO reservations (
                        reservation_code, status,
                        customer_name, customer_email, customer_phone, customer_address, customer_note,
                        date_start, date_end, time_start, time_end, total_days,
                        subtotal, weekend_markup, discount, delivery_option, delivery_fee, total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $reservationCode, 'pending',
                    $customerName, $customerEmail, $customerPhone, $customerAddress, $customerNote,
                    $dateStart, $dateEnd, $timeStart, $timeEnd, $totalDays,
                    $subtotal, $weekendMarkupTotal, $discountTotal, $deliveryOption, $deliveryFee, $total
                ]);
                
                // Create reservation items
                $telegramItems = [];
                foreach ($cartItems as $item) {
                    if ($item['type'] === 'service') {
                        db()->insert("
                            INSERT INTO reservation_items (reservation_id, tool_id, tool_name, price_per_day, days, subtotal, item_type, service_description, service_date, service_location)
                            VALUES (?, NULL, ?, 0, 0, 0, 'service', ?, ?, ?)
                        ", [
                            $reservationId,
                            $item['service_label'],
                            $item['description'],
                            $item['service_date'],
                            $item['location']
                        ]);
                        $telegramItems[] = [
                            'type' => 'service',
                            'tool_name' => $item['service_label'],
                            'description' => $item['description'],
                            'service_date' => $item['service_date'],
                            'location' => $item['location'],
                            'price' => 0
                        ];
                    } else {
                        db()->insert("
                            INSERT INTO reservation_items (reservation_id, tool_id, tool_name, price_per_day, days, subtotal, item_type)
                            VALUES (?, ?, ?, ?, ?, ?, 'tool')
                        ", [
                            $reservationId,
                            $item['tool_id'],
                            $item['tool_name'],
                            $item['price_24h'],
                            $item['total_days'],
                            $item['subtotal']
                        ]);
                        $telegramItems[] = [
                            'type' => 'tool',
                            'tool_name' => $item['tool_name'],
                            'price' => $item['subtotal']
                        ];
                    }
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
                    'time_start' => $timeStart,
                    'time_end' => $timeEnd,
                    'total_price' => $total,
                    'deposit_total' => 0,
                    'notes' => $customerNote
                ];
                
                $telegramMessage = formatReservationTelegramMessage($reservationData, $telegramItems);
                sendTelegramNotification($telegramMessage);
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Store reservation code for thank you page
                $_SESSION['last_reservation'] = $reservationCode;
                
                redirect('zahvalnica');
                
            } catch (Exception $e) {
                db()->rollback();
                error_log("Checkout error: " . $e->getMessage());
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
                    <div class="phone-input-wrapper">
                        <select id="phone_prefix" name="phone_prefix" class="form-control phone-prefix" required>
                            <option value="+381"<?= post('phone_prefix') === '+381' || !post('phone_prefix') ? ' selected' : '' ?>>+381 (Srbija)</option>
                            <option value="+382"<?= post('phone_prefix') === '+382' ? ' selected' : '' ?>>+382 (Crna Gora)</option>
                            <option value="+385"<?= post('phone_prefix') === '+385' ? ' selected' : '' ?>>+385 (Hrvatska)</option>
                            <option value="+387"<?= post('phone_prefix') === '+387' ? ' selected' : '' ?>>+387 (Bosna)</option>
                            <option value="+386"<?= post('phone_prefix') === '+386' ? ' selected' : '' ?>>+386 (Slovenija)</option>
                            <option value="+389"<?= post('phone_prefix') === '+389' ? ' selected' : '' ?>>+389 (Makedonija)</option>
                            <option value="+359"<?= post('phone_prefix') === '+359' ? ' selected' : '' ?>>+359 (Bugarska)</option>
                            <option value="+40"<?= post('phone_prefix') === '+40' ? ' selected' : '' ?>>+40 (Rumunija)</option>
                            <option value="+36"<?= post('phone_prefix') === '+36' ? ' selected' : '' ?>>+36 (Mađarska)</option>
                            <option value="+49"<?= post('phone_prefix') === '+49' ? ' selected' : '' ?>>+49 (Nemačka)</option>
                            <option value="+41"<?= post('phone_prefix') === '+41' ? ' selected' : '' ?>>+41 (Švajcarska)</option>
                            <option value="+43"<?= post('phone_prefix') === '+43' ? ' selected' : '' ?>>+43 (Austrija)</option>
                            <option value="+30"<?= post('phone_prefix') === '+30' ? ' selected' : '' ?>>+30 (Grčka)</option>
                            <option value="+39"<?= post('phone_prefix') === '+39' ? ' selected' : '' ?>>+39 (Italija)</option>
                        </select>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control phone-number" 
                               placeholder="06xxxxxxxx" value="<?= e(post('customer_phone')) ?>" required>
                    </div>
                </div>
                <style>
                .phone-input-wrapper {
                    display: flex;
                    gap: 0;
                }
                .phone-input-wrapper .phone-prefix {
                    flex: 0 0 180px;
                    border-radius: var(--border-radius) 0 0 var(--border-radius);
                    border-right: none;
                }
                .phone-input-wrapper .phone-number {
                    flex: 1;
                    border-radius: 0 var(--border-radius) var(--border-radius) 0;
                }
                </style>
                
                <div class="form-group">
                    <label for="customer_address" class="form-label" id="addressLabel">Adresa</label>
                    <textarea id="customer_address" name="customer_address" class="form-control" rows="2"><?= e(post('customer_address')) ?></textarea>
                    <p class="form-text">Obavezno za dostavu</p>
                </div>
                
                <div class="form-group">
                    <label for="customer_note" class="form-label">Napomena</label>
                    <textarea id="customer_note" name="customer_note" class="form-control" rows="2" 
                              placeholder="Recimo u koje vreme bi ste preuzeli alat."><?= e(post('customer_note')) ?></textarea>
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
                        <th>Stavka</th>
                        <th>Datum</th>
                        <th>Info</th>
                        <th class="text-right">Cena</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <?php if ($item['type'] === 'service'): ?>
                    <tr>
                        <td><?= e($item['service_label']) ?></td>
                        <td><?= formatDate($item['service_date']) ?></td>
                        <td><?= $item['location'] === 'workshop' ? 'Radionica' : 'Na adresi' ?></td>
                        <td class="text-right"><em>Dogovor</em></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td><?= e($item['tool_name']) ?></td>
                        <td><?= formatDate($item['date_start']) ?> <?= e($item['time_start'] ?? '') ?>h<br><small>do <?= formatDate($item['date_end']) ?> <?= e($item['time_end'] ?? '') ?>h</small></td>
                        <td>
                            <?= $item['total_days'] ?> <?= $item['total_days'] === 1 ? 'dan' : 'dana' ?>
                            <?php if ($item['regular_days'] > 0 || $item['weekend_days'] > 0): ?>
                            <br><small>
                                <?php if ($item['regular_days'] > 0): ?>
                                    <?= $item['regular_days'] ?> × <?= formatPrice($item['price_24h']) ?>
                                <?php endif; ?>
                                <?php if ($item['weekend_days'] > 0): ?>
                                    <?php if ($item['regular_days'] > 0): ?> + <?php endif; ?>
                                    <?= $item['weekend_days'] ?> × <?= formatPrice($item['price_24h'] * (1 + WEEKEND_MARKUP)) ?>
                                <?php endif; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?= formatPrice($item['subtotal'] + ($item['weekend_days'] * $item['price_24h'] * WEEKEND_MARKUP) - $item['discount']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Alati ukupno:</td>
                        <td class="text-right"><strong><?= formatPrice($subtotal) ?></strong></td>
                    </tr>
                    <?php if ($weekendMarkupTotal > 0): ?>
                    <tr>
                        <td colspan="3">Vikend doplata (+<?= WEEKEND_MARKUP * 100 ?>%):</td>
                        <td class="text-right">+<?= formatPrice($weekendMarkupTotal) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($discountTotal > 0): ?>
                    <tr>
                        <td colspan="3">Popust (7+ dana):</td>
                        <td class="text-right">-<?= formatPrice($discountTotal) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($hasServices): ?>
                    <tr>
                        <td colspan="3">Usluge:</td>
                        <td class="text-right"><em>Dogovor</em></td>
                    </tr>
                    <?php endif; ?>
                    <tr id="deliveryRow">
                        <td colspan="3"><?= $hasServices ? 'Dostava alata:' : 'Dostava:' ?></td>
                        <td class="text-right" id="deliveryPrice">Besplatno</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3"><strong>UKUPNO:</strong></td>
                        <td class="text-right"><strong id="grandTotal"><?= formatPrice($subtotal + $weekendMarkupTotal - $discountTotal) ?></strong></td>
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
const baseSubtotal = <?= $subtotal ?>;
const weekendMarkupTotal = <?= $weekendMarkupTotal ?>;
const discountTotal = <?= $discountTotal ?>;
const deliveryPrices = <?= json_encode(array_map(fn($o) => $o['price'], $deliveryOptions)) ?>;

function updateDelivery() {
    const selected = document.querySelector('input[name="delivery_option"]:checked').value;
    const deliveryPrice = deliveryPrices[selected] || 0;
    const total = baseSubtotal + weekendMarkupTotal - discountTotal + deliveryPrice;
    
    document.getElementById('deliveryPrice').textContent = deliveryPrice > 0 
        ? deliveryPrice.toFixed(2) + ' €' 
        : 'Besplatno';
    document.getElementById('grandTotal').textContent = total.toFixed(2) + ' €';
    
    // Update address requirement
    const addressLabel = document.getElementById('addressLabel');
    if (selected === 'workshop') {
        addressLabel.classList.remove('required');
    } else {
        addressLabel.classList.add('required');
    }
}

// Initial update
updateDelivery();
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
