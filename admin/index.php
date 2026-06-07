<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/google-calendar.php';

requireAdmin();

$action = get('action', 'list');
$id = (int) get('id', 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'status') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/');
    }

    $newStatus = post('status');
    $resId = (int) post('reservation_id');

    $validStatuses = ['pending', 'confirmed', 'rented', 'completed', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        db()->execute(
            "UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP" .
            ($newStatus === 'cancelled' ? ", cancelled_at = CURRENT_TIMESTAMP" : "") .
            " WHERE id = ?",
            [$newStatus, $resId]
        );

        flash('success', 'Status rezervacije je ažuriran.');

        if ($newStatus === 'confirmed') {
            $confirmedRes = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$resId]);
            $confirmedItems = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$resId]);
            if ($confirmedRes && empty($confirmedRes['google_event_id'])) {
                googleCalendarCreateEvent($confirmedRes, $confirmedItems);
            }
        }
    }
    redirect('admin/');
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'obrisi') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/');
    }

    $resId = (int) post('reservation_id', $id);

    $reservation = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$resId]);
    if (!$reservation) {
        flash('error', 'Rezervacija nije pronađena.');
        redirect('admin/');
    }

    db()->beginTransaction();
    try {
        db()->execute("DELETE FROM reservation_items WHERE reservation_id = ?", [$resId]);
        db()->execute("DELETE FROM reservations WHERE id = ?", [$resId]);
        db()->commit();
        flash('success', 'Rezervacija #' . $reservation['reservation_code'] . ' je obrisana.');
    } catch (Exception $e) {
        db()->rollback();
        flash('error', 'Greška pri brisanju rezervacije.');
    }
    redirect('admin/');
}

// Handle edit save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'izmeni') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/?action=izmeni&id=' . $id);
    }

    $resId = (int) post('reservation_id', $id);
    $reservation = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$resId]);
    if (!$reservation) {
        flash('error', 'Rezervacija nije pronađena.');
        redirect('admin/');
    }

    $customerName = trim(post('customer_name'));
    $customerEmail = trim(post('customer_email'));
    $customerPhone = trim(post('customer_phone'));
    $customerAddress = trim(post('customer_address'));
    $customerNote = trim(post('customer_note'));
    $adminNote = trim(post('admin_note'));
    $dateStart = post('date_start');
    $dateEnd = post('date_end');
    $timeStart = post('time_start', '08:00');
    $timeEnd = post('time_end', '18:00');
    $deliveryOption = post('delivery_option', 'pickup');

    $errors = [];
    if (empty($customerName)) $errors[] = 'Ime je obavezno.';
    if (empty($dateStart)) $errors[] = 'Datum početka je obavezan.';
    if (empty($dateEnd)) $errors[] = 'Datum završetka je obavezan.';
    if ($dateEnd < $dateStart) $errors[] = 'Datum završetka mora biti posle datuma početka.';

    $removedItems = post('remove_items', []);
    if (!is_array($removedItems)) $removedItems = [];

    $newToolIds = post('new_tool_ids', []);
    if (!is_array($newToolIds)) $newToolIds = [];

    if (empty($errors)) {
        db()->beginTransaction();
        try {
            $deliveryFee = 0;
            if ($deliveryOption === 'delivery') $deliveryFee = DELIVERY_ONEWAY;
            elseif ($deliveryOption === 'roundtrip') $deliveryFee = DELIVERY_ROUNDTRIP;

            $existingItems = db()->fetchAll(
                "SELECT * FROM reservation_items WHERE reservation_id = ? AND item_type = 'tool'",
                [$resId]
            );

            $totalDays = calculateRentalDays($dateStart, $dateEnd, $timeStart, $timeEnd);

            $dates = getDatesBetween($dateStart, $dateEnd);
            $datesSlice = array_slice($dates, 0, $totalDays);
            $weekendCount = 0;
            foreach ($datesSlice as $date) {
                if (isWeekend($date)) $weekendCount++;
            }

            $subtotal = 0;
            $weekendMarkup = 0;
            $discount = 0;

            foreach ($existingItems as $item) {
                if (in_array($item['id'], $removedItems)) {
                    db()->execute("DELETE FROM reservation_items WHERE id = ?", [$item['id']]);
                    continue;
                }
                $itemSubtotal = $item['price_per_day'] * $totalDays;
                $itemMarkup = $weekendCount * $item['price_per_day'] * WEEKEND_MARKUP;
                db()->execute(
                    "UPDATE reservation_items SET days = ?, subtotal = ? WHERE id = ?",
                    [$totalDays, $itemSubtotal, $item['id']]
                );
                $subtotal += $itemSubtotal;
                $weekendMarkup += $itemMarkup;
            }

            foreach ($newToolIds as $idx => $toolId) {
                $toolId = (int) $toolId;
                if ($toolId <= 0) continue;

                $tool = db()->fetch("SELECT id, name, price_24h FROM tools WHERE id = ?", [$toolId]);
                if (!$tool) continue;

                $toolSubtotal = $tool['price_24h'] * $totalDays;
                $toolMarkup = $weekendCount * $tool['price_24h'] * WEEKEND_MARKUP;
                db()->execute("
                    INSERT INTO reservation_items (reservation_id, tool_id, tool_name, price_per_day, days, subtotal, item_type)
                    VALUES (?, ?, ?, ?, ?, ?, 'tool')
                ", [
                    $resId, $tool['id'], $tool['name'], $tool['price_24h'], $totalDays, $toolSubtotal
                ]);
                $subtotal += $toolSubtotal;
                $weekendMarkup += $toolMarkup;
            }

            if ($totalDays >= 7) {
                $discount = ($subtotal + $weekendMarkup) * WEEKLY_DISCOUNT;
            }

            $total = $subtotal + $weekendMarkup - $discount + $deliveryFee;

            db()->execute("
                UPDATE reservations SET
                    customer_name = ?, customer_email = ?, customer_phone = ?, customer_address = ?, customer_note = ?,
                    date_start = ?, date_end = ?, time_start = ?, time_end = ?,
                    total_days = ?, subtotal = ?, weekend_markup = ?, discount = ?,
                    delivery_option = ?, delivery_fee = ?, total = ?,
                    admin_note = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [
                $customerName, $customerEmail, $customerPhone, $customerAddress, $customerNote,
                $dateStart, $dateEnd, $timeStart, $timeEnd,
                $totalDays, $subtotal, $weekendMarkup, $discount,
                $deliveryOption, $deliveryFee, $total,
                $adminNote, $resId
            ]);

            db()->commit();

            if ($reservation['status'] === 'confirmed') {
                $updatedRes = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$resId]);
                $updatedItems = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$resId]);
                if ($updatedRes) {
                    if (!empty($updatedRes['google_event_id'])) {
                        googleCalendarUpdateEvent($updatedRes, $updatedItems);
                    } else {
                        googleCalendarCreateEvent($updatedRes, $updatedItems);
                    }
                }
            }

            flash('success', 'Rezervacija #' . $reservation['reservation_code'] . ' je uspešno izmenjena.');
            redirect('admin/?action=detalji&id=' . $resId);

        } catch (Exception $e) {
            db()->rollback();
            error_log("Edit reservation error: " . $e->getMessage());
            $errors[] = 'Greška pri izmeni rezervacije: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect('admin/?action=izmeni&id=' . $resId);
    }
}

$filterStatus = get('status', '');

$where = "1=1";
$params = [];
if ($filterStatus) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
}

$reservations = db()->fetchAll("
    SELECT * FROM reservations
    WHERE {$where}
    ORDER BY created_at DESC
", $params);

$allTools = db()->fetchAll("SELECT id, name, price_24h FROM tools WHERE status = 'available' ORDER BY name");

$reservation = null;
$reservationItems = [];
if (($action === 'detalji' || $action === 'izmeni' || $action === 'obrisi') && $id) {
    $reservation = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$id]);
    if (!$reservation) {
        flash('error', 'Rezervacija nije pronađena.');
        redirect('admin/');
    }
    $reservationItems = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$id]);
}

$pageTitle = 'Dashboard - Admin';

ob_start();
?>

<?php if ($action === 'list'): ?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
</div>

<?php
$stats = [
    'tools' => db()->fetchColumn("SELECT COUNT(*) FROM tools"),
    'tools_available' => db()->fetchColumn("SELECT COUNT(*) FROM tools WHERE status = 'available'"),
    'categories' => db()->fetchColumn("SELECT COUNT(*) FROM categories WHERE active = 1"),
    'reservations' => db()->fetchColumn("SELECT COUNT(*) FROM reservations"),
    'reservations_pending' => db()->fetchColumn("SELECT COUNT(*) FROM reservations WHERE status = 'pending'"),
    'reservations_rented' => db()->fetchColumn("SELECT COUNT(*) FROM reservations WHERE status = 'rented'"),
];
?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $stats['tools'] ?></span>
        <span class="stat-label">Ukupno alata</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $stats['tools_available'] ?></span>
        <span class="stat-label">Dostupnih alata</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $stats['categories'] ?></span>
        <span class="stat-label">Kategorija</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $stats['reservations'] ?></span>
        <span class="stat-label">Ukupno rezervacija</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $stats['reservations_pending'] ?></span>
        <span class="stat-label">Na čekanju</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $stats['reservations_rented'] ?></span>
        <span class="stat-label">Iznajmljeno</span>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Rezervacije (<?= count($reservations) ?>)</h3>
    </div>

    <form method="GET" class="d-flex gap-2 flex-wrap mb-3">
        <input type="hidden" name="action" value="list">
        <select name="status" class="form-control" style="max-width: 170px;">
            <option value="">Svi statusi</option>
            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Na čekanju</option>
            <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Potvrđena</option>
            <option value="rented" <?= $filterStatus === 'rented' ? 'selected' : '' ?>>Iznajmljeno</option>
            <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Završena</option>
            <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Otkazana</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filtriraj</button>
        <?php if ($filterStatus): ?>
            <a href="<?= url('admin/') ?>" class="btn btn-secondary">Resetuj</a>
        <?php endif; ?>
    </form>

    <?php if (empty($reservations)): ?>
        <p class="text-muted">Nema rezervacija.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Kupac</th>
                        <th>Kontakt</th>
                        <th>Datumi</th>
                        <th>Ukupno</th>
                        <th>Status</th>
                        <th>Kreirana</th>
                        <th class="actions">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                    <tr data-status="<?= $res['status'] ?>">
                        <td><strong><?= e($res['reservation_code']) ?></strong></td>
                        <td><?= e($res['customer_name']) ?></td>
                        <td>
                            <small>
                                <?= e($res['customer_phone']) ?><br>
                                <?= e($res['customer_email']) ?>
                            </small>
                        </td>
                        <td>
                            <?= formatDate($res['date_start']) ?> <?= e($res['time_start'] ?? '') ?><br>
                            <small>do <?= formatDate($res['date_end']) ?> <?= e($res['time_end'] ?? '') ?></small>
                        </td>
                        <td><strong><?= formatPrice($res['total']) ?></strong></td>
                        <td>
                            <form method="POST" action="<?= url('admin/?action=status') ?>" style="margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                <select name="status" class="form-control"
                                        style="width: auto; min-width: 120px; padding: 4px 24px 4px 8px; font-size: 12px;"
                                        onchange="this.form.submit()">
                                    <option value="pending" <?= $res['status'] === 'pending' ? 'selected' : '' ?>>Na čekanju</option>
                                    <option value="confirmed" <?= $res['status'] === 'confirmed' ? 'selected' : '' ?>>Potvrđena</option>
                                    <option value="rented" <?= $res['status'] === 'rented' ? 'selected' : '' ?>>Iznajmljeno</option>
                                    <option value="completed" <?= $res['status'] === 'completed' ? 'selected' : '' ?>>Završena</option>
                                    <option value="cancelled" <?= $res['status'] === 'cancelled' ? 'selected' : '' ?>>Otkazana</option>
                                </select>
                            </form>
                        </td>
                        <td><small><?= formatDateTime($res['created_at']) ?></small></td>
                        <td class="actions">
                            <a href="<?= url('admin/?action=detalji&id=' . $res['id']) ?>" class="btn btn-secondary btn-small">Detalji</a>
                            <a href="<?= url('admin/?action=izmeni&id=' . $res['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                            <a href="<?= url('admin/?action=obrisi&id=' . $res['id']) ?>" class="btn btn-danger btn-small"
                               data-confirm="Obrisati rezervaciju #<?= e($res['reservation_code']) ?>? Ova radnja je nepovratna!">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Brze akcije</h3>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('admin/alati/dodaj') ?>" class="btn btn-primary">+ Dodaj alat</a>
        <a href="<?= url('admin/kategorije/dodaj') ?>" class="btn btn-secondary">+ Dodaj kategoriju</a>
        <a href="<?= url('admin/blokirani-datumi/dodaj') ?>" class="btn btn-secondary">+ Blokiraj datum</a>
        <a href="<?= url('') ?>" class="btn btn-secondary" target="_blank">Pogledaj sajt →</a>
    </div>
</div>

<?php elseif ($action === 'detalji'): ?>

<div class="admin-page-header">
    <h1>Rezervacija #<?= e($reservation['reservation_code']) ?></h1>
    <a href="<?= url('admin/') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Podaci o kupcu</h3>
        <span class="status-badge status-<?= $reservation['status'] ?>"><?= reservationStatusLabel($reservation['status']) ?></span>
    </div>

    <div class="form-row">
        <div>
            <p><strong>Ime:</strong> <?= e($reservation['customer_name']) ?></p>
            <p><strong>Telefon:</strong> <?= e($reservation['customer_phone']) ?></p>
            <p><strong>Email:</strong> <?= e($reservation['customer_email'] ?? '-') ?></p>
        </div>
        <div>
            <p><strong>Adresa:</strong> <?= e($reservation['customer_address'] ?? '-') ?></p>
            <p><strong>Napomena:</strong> <?= e($reservation['customer_note'] ?? '-') ?></p>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Stavke rezervacije</h3>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Alat</th>
                <th>Cena/dan</th>
                <th>Dana</th>
                <th>Ukupno</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservationItems as $item): ?>
            <tr>
                <td><?= e($item['tool_name']) ?></td>
                <td><?= formatPrice($item['price_per_day']) ?></td>
                <td><?= $item['days'] ?></td>
                <td><?= formatPrice($item['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Finansije</h3>
    </div>

    <?php
    $displayDates = getDatesBetween($reservation['date_start'], $reservation['date_end']);
    $displayTotalDays = max(1, (int)$reservation['total_days']);
    $displayDatesSlice = array_slice($displayDates, 0, $displayTotalDays);
    $displayWeekendCount = 0;
    foreach ($displayDatesSlice as $date) {
        if (isWeekend($date)) $displayWeekendCount++;
    }

    $displayBase = 0;
    $displayMarkup = 0;
    foreach ($reservationItems as $item) {
        if ($item['item_type'] === 'service') continue;
        $displayBase += $item['price_per_day'] * $item['days'];
        $displayMarkup += $displayWeekendCount * $item['price_per_day'] * WEEKEND_MARKUP;
    }
    $displayDiscount = (float)$reservation['discount'];
    $displayDelivery = (float)$reservation['delivery_fee'];
    $displayTotal = $displayBase + $displayMarkup - $displayDiscount + $displayDelivery;
    ?>

    <div class="form-row">
        <div>
            <p><strong>Period:</strong> <?= formatDate($reservation['date_start']) ?> - <?= formatDate($reservation['date_end']) ?></p>
            <p><strong>Vreme preuzimanja:</strong> <?= e($reservation['time_start'] ?? '08:00') ?>h</p>
            <p><strong>Vreme povratka:</strong> <?= e($reservation['time_end'] ?? '18:00') ?>h</p>
            <p><strong>Trajanje:</strong> <?= formatRentalDuration(calculateRentalHours($reservation['date_start'], $reservation['date_end'], $reservation['time_start'] ?? '08:00', $reservation['time_end'] ?? '18:00')) ?></p>
            <p><strong>Dostava:</strong> <?= ucfirst($reservation['delivery_option']) ?></p>
        </div>
        <div>
            <p><strong>Osnovna cena:</strong> <?= formatPrice($displayBase) ?></p>
            <?php if ($displayMarkup > 0): ?>
            <p><strong>Vikend doplata (+<?= WEEKEND_MARKUP * 100 ?>%):</strong> +<?= formatPrice($displayMarkup) ?></p>
            <?php endif; ?>
            <p><strong>Međuzbir:</strong> <?= formatPrice($displayBase + $displayMarkup) ?></p>
            <?php if ($displayDiscount > 0): ?>
            <p><strong>Popust (<?= WEEKLY_DISCOUNT * 100 ?>%):</strong> -<?= formatPrice($displayDiscount) ?></p>
            <?php endif; ?>
            <p><strong>Dostava:</strong> <?= formatPrice($displayDelivery) ?></p>
            <p style="font-size: 1.2em;"><strong>UKUPNO: <?= formatPrice($displayTotal) ?></strong></p>
        </div>
    </div>
</div>

<?php if (!empty($reservation['admin_note'])): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Admin napomena</h3>
    </div>
    <p><?= nl2br(e($reservation['admin_note'])) ?></p>
</div>
<?php endif; ?>

<div class="form-actions" style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
    <a href="<?= url('admin/?action=izmeni&id=' . $reservation['id']) ?>" class="btn btn-primary">Izmeni rezervaciju</a>
    <a href="<?= url('admin/?action=obrisi&id=' . $reservation['id']) ?>" class="btn btn-danger"
       data-confirm="Obrisati rezervaciju #<?= e($reservation['reservation_code']) ?>? Ova radnja je nepovratna!">Obriši rezervaciju</a>
</div>

<?php elseif ($action === 'izmeni'): ?>

<?php
$formData = [
    'customer_name' => post('customer_name', $reservation['customer_name']),
    'customer_email' => post('customer_email', $reservation['customer_email'] ?? ''),
    'customer_phone' => post('customer_phone', $reservation['customer_phone']),
    'customer_address' => post('customer_address', $reservation['customer_address'] ?? ''),
    'customer_note' => post('customer_note', $reservation['customer_note'] ?? ''),
    'admin_note' => post('admin_note', $reservation['admin_note'] ?? ''),
    'date_start' => post('date_start', $reservation['date_start']),
    'date_end' => post('date_end', $reservation['date_end']),
    'time_start' => post('time_start', $reservation['time_start'] ?? '08:00'),
    'time_end' => post('time_end', $reservation['time_end'] ?? '18:00'),
    'delivery_option' => post('delivery_option', $reservation['delivery_option']),
];
?>

<div class="admin-page-header">
    <h1>Izmeni rezervaciju #<?= e($reservation['reservation_code']) ?></h1>
    <div>
        <a href="<?= url('admin/?action=detalji&id=' . $id) ?>" class="btn btn-secondary">← Nazad na detalje</a>
        <a href="<?= url('admin/') ?>" class="btn btn-secondary">← Spisak</a>
    </div>
</div>

<form method="POST" class="admin-form" action="<?= url('admin/?action=izmeni&id=' . $id) ?>">
    <?= csrfField() ?>
    <input type="hidden" name="reservation_id" value="<?= $id ?>">

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Podaci o kupcu</h3>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="customer_name" class="form-label required">Ime i prezime</label>
                <input type="text" id="customer_name" name="customer_name" class="form-control"
                       value="<?= e($formData['customer_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="customer_phone" class="form-label required">Telefon</label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-control"
                       value="<?= e($formData['customer_phone']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="customer_email" class="form-label">Email</label>
                <input type="email" id="customer_email" name="customer_email" class="form-control"
                       value="<?= e($formData['customer_email']) ?>">
            </div>
            <div class="form-group">
                <label for="delivery_option" class="form-label">Dostava</label>
                <select id="delivery_option" name="delivery_option" class="form-control">
                    <option value="pickup" <?= $formData['delivery_option'] === 'pickup' ? 'selected' : '' ?>>Lično preuzimanje</option>
                    <option value="delivery" <?= $formData['delivery_option'] === 'delivery' ? 'selected' : '' ?>>Dostava (+<?= formatPrice(DELIVERY_ONEWAY) ?>)</option>
                    <option value="roundtrip" <?= $formData['delivery_option'] === 'roundtrip' ? 'selected' : '' ?>>Dostava + povratak (+<?= formatPrice(DELIVERY_ROUNDTRIP) ?>)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="customer_address" class="form-label">Adresa</label>
            <textarea id="customer_address" name="customer_address" class="form-control" rows="2"><?= e($formData['customer_address']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="customer_note" class="form-label">Napomena kupca</label>
            <textarea id="customer_note" name="customer_note" class="form-control" rows="2"><?= e($formData['customer_note']) ?></textarea>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Datum i vreme</h3>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date_start" class="form-label required">Datum početka</label>
                <input type="date" id="date_start" name="date_start" class="form-control"
                       value="<?= e($formData['date_start']) ?>" required>
            </div>
            <div class="form-group">
                <label for="date_end" class="form-label required">Datum završetka</label>
                <input type="date" id="date_end" name="date_end" class="form-control"
                       value="<?= e($formData['date_end']) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="time_start" class="form-label">Vreme preuzimanja</label>
                <input type="time" id="time_start" name="time_start" class="form-control"
                       value="<?= e($formData['time_start']) ?>">
            </div>
            <div class="form-group">
                <label for="time_end" class="form-label">Vreme povratka</label>
                <input type="time" id="time_end" name="time_end" class="form-control"
                       value="<?= e($formData['time_end']) ?>">
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Stavke rezervacije</h3>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Alat</th>
                    <th>Cena/dan</th>
                    <th>Dana</th>
                    <th>Ukupno</th>
                    <th class="actions">Ukloni</th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <?php foreach ($reservationItems as $item): ?>
                <?php if ($item['item_type'] === 'service') continue; ?>
                <tr>
                    <td><?= e($item['tool_name']) ?></td>
                    <td><?= formatPrice($item['price_per_day']) ?></td>
                    <td><?= $item['days'] ?></td>
                    <td><?= formatPrice($item['subtotal']) ?></td>
                    <td class="actions">
                        <label class="btn btn-danger btn-small" style="cursor: pointer;">
                            <input type="checkbox" name="remove_items[]" value="<?= $item['id'] ?>"
                                   onchange="this.closest('label').classList.toggle('btn-danger-active')">
                            Ukloni
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php foreach ($reservationItems as $item): ?>
                <?php if ($item['item_type'] !== 'service') continue; ?>
                <tr>
                    <td><em><?= e($item['tool_name']) ?></em></td>
                    <td colspan="2"><small><?= e($item['service_description']) ?></small></td>
                    <td><em>Dogovor</em></td>
                    <td class="actions">
                        <label class="btn btn-danger btn-small" style="cursor: pointer;">
                            <input type="checkbox" name="remove_items[]" value="<?= $item['id'] ?>"
                                   onchange="this.closest('label').classList.toggle('btn-danger-active')">
                            Ukloni
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="form-row" style="margin-top: var(--spacing-lg); align-items: flex-end;">
            <div class="form-group">
                <label for="new_tool" class="form-label">Dodaj alat</label>
                <select id="new_tool" class="form-control" style="min-width: 250px;">
                    <option value="">-- Izaberite alat --</option>
                    <?php foreach ($allTools as $t): ?>
                    <option value="<?= $t['id'] ?>" data-price="<?= $t['price_24h'] ?>">
                        <?= e($t['name']) ?> (<?= formatPrice($t['price_24h']) ?>/dan)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-secondary" onclick="addToolItem()">+ Dodaj</button>
            </div>
        </div>

        <div id="newItemsContainer"></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Admin napomena</h3>
        </div>
        <div class="form-group">
            <textarea id="admin_note" name="admin_note" class="form-control" rows="3"
                      placeholder="Interne napreme (nevidljive kupcu)"><?= e($formData['admin_note']) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Sačuvaj izmene</button>
        <a href="<?= url('admin/?action=detalji&id=' . $id) ?>" class="btn btn-secondary">Otkaži</a>
    </div>
</form>

<script>
function addToolItem() {
    const select = document.getElementById('new_tool');
    const container = document.getElementById('newItemsContainer');

    if (!select.value) {
        alert('Izaberite alat.');
        return;
    }

    const option = select.options[select.selectedIndex];
    const toolId = select.value;
    const toolName = option.textContent;

    if (container.querySelector(`[data-tool-id="${toolId}"]`)) {
        alert('Ovaj alat je već dodat.');
        return;
    }

    const div = document.createElement('div');
    div.className = 'spec-row';
    div.dataset.toolId = toolId;
    div.style.marginTop = '8px';
    div.innerHTML = `
        <input type="hidden" name="new_tool_ids[]" value="${toolId}">
        <span style="flex: 1; padding: 6px 8px; background: var(--color-gray-100); border-radius: var(--border-radius);">
            <strong>${toolName}</strong>
        </span>
        <button type="button" class="btn btn-danger btn-small" onclick="this.closest('.spec-row').remove()">&times;</button>
    `;
    container.appendChild(div);

    select.value = '';
}
</script>

<style>
.btn-danger-active {
    background: var(--color-error) !important;
    border-color: var(--color-error) !important;
    color: #fff !important;
}
.btn-danger-active input[type="checkbox"] {
    accent-color: #fff;
}
</style>

<?php elseif ($action === 'obrisi'): ?>

<div class="admin-page-header">
    <h1>Brisanje rezervacije #<?= e($reservation['reservation_code']) ?></h1>
    <a href="<?= url('admin/') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card" style="border: 2px solid var(--color-error);">
    <div class="admin-card-header">
        <h3 style="color: var(--color-error);">Potvrda brisanja</h3>
    </div>

    <div class="alert alert-error">
        <strong>Pažnja!</strong> Ova radnja je nepovratna. Svi podaci o rezervaciji će biti trajno obrisani.
    </div>

    <div class="form-row">
        <div>
            <p><strong>Kupac:</strong> <?= e($reservation['customer_name']) ?></p>
            <p><strong>Telefon:</strong> <?= e($reservation['customer_phone']) ?></p>
            <p><strong>Period:</strong> <?= formatDate($reservation['date_start']) ?> - <?= formatDate($reservation['date_end']) ?></p>
        </div>
        <div>
            <p><strong>Status:</strong> <?= reservationStatusLabel($reservation['status']) ?></p>
            <p><strong>Ukupno:</strong> <?= formatPrice($reservation['total']) ?></p>
            <p><strong>Stavki:</strong> <?= count($reservationItems) ?></p>
        </div>
    </div>

    <form method="POST" action="<?= url('admin/?action=obrisi&id=' . $id) ?>"
          onsubmit="return confirm('Da li ste potpuno sigurni? Ova radnja je nepovratna!')">
        <?= csrfField() ?>
        <input type="hidden" name="reservation_id" value="<?= $id ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Da, obriši rezervaciju</button>
            <a href="<?= url('admin/?action=detalji&id=' . $id) ?>" class="btn btn-secondary">Ne, nazad na detalje</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
