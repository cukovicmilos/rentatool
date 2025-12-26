<?php
/**
 * Admin Reservations Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$action = get('action', 'list');
$id = (int) get('id', 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'status') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/rezervacije');
    }
    
    $newStatus = post('status');
    $resId = (int) post('reservation_id');
    
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $updateData = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if ($newStatus === 'cancelled') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
        }
        
        db()->execute(
            "UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP" . 
            ($newStatus === 'cancelled' ? ", cancelled_at = CURRENT_TIMESTAMP" : "") .
            " WHERE id = ?",
            [$newStatus, $resId]
        );
        
        flash('success', 'Status rezervacije je ažuriran.');
    }
    redirect('admin/rezervacije');
}

// Get reservation for detail view
$reservation = null;
$reservationItems = [];
if ($action === 'detalji' && $id) {
    $reservation = db()->fetch("SELECT * FROM reservations WHERE id = ?", [$id]);
    if (!$reservation) {
        flash('error', 'Rezervacija nije pronađena.');
        redirect('admin/rezervacije');
    }
    $reservationItems = db()->fetchAll("SELECT * FROM reservation_items WHERE reservation_id = ?", [$id]);
}

// Filter params
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

$pageTitle = 'Rezervacije - Admin';

ob_start();
?>

<?php if ($action === 'list'): ?>

<div class="admin-page-header">
    <h1>Rezervacije (<?= count($reservations) ?>)</h1>
</div>

<div class="admin-card">
    <form method="GET" class="d-flex gap-2 flex-wrap mb-3">
        <select name="status" class="form-control" style="max-width: 150px;">
            <option value="">Svi statusi</option>
            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Na čekanju</option>
            <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Potvrđena</option>
            <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Završena</option>
            <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Otkazana</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filtriraj</button>
        <?php if ($filterStatus): ?>
            <a href="<?= url('admin/rezervacije') ?>" class="btn btn-secondary">Resetuj</a>
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
                    <tr>
                        <td><strong><?= e($res['reservation_code']) ?></strong></td>
                        <td><?= e($res['customer_name']) ?></td>
                        <td>
                            <small>
                                <?= e($res['customer_phone']) ?><br>
                                <?= e($res['customer_email']) ?>
                            </small>
                        </td>
                        <td>
                            <?= formatDate($res['date_start']) ?><br>
                            <small>do <?= formatDate($res['date_end']) ?></small>
                        </td>
                        <td><strong><?= formatPrice($res['total']) ?></strong></td>
                        <td>
                            <form method="POST" action="<?= url('admin/rezervacije/status') ?>" style="margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                <select name="status" class="form-control" style="width: auto; padding: 4px 8px; font-size: 12px;"
                                        onchange="this.form.submit()">
                                    <option value="pending" <?= $res['status'] === 'pending' ? 'selected' : '' ?>>Na čekanju</option>
                                    <option value="confirmed" <?= $res['status'] === 'confirmed' ? 'selected' : '' ?>>Potvrđena</option>
                                    <option value="completed" <?= $res['status'] === 'completed' ? 'selected' : '' ?>>Završena</option>
                                    <option value="cancelled" <?= $res['status'] === 'cancelled' ? 'selected' : '' ?>>Otkazana</option>
                                </select>
                            </form>
                        </td>
                        <td><small><?= formatDateTime($res['created_at']) ?></small></td>
                        <td class="actions">
                            <a href="<?= url('admin/rezervacije/detalji/' . $res['id']) ?>" class="btn btn-secondary btn-small">Detalji</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php elseif ($action === 'detalji'): ?>

<div class="admin-page-header">
    <h1>Rezervacija #<?= e($reservation['reservation_code']) ?></h1>
    <a href="<?= url('admin/rezervacije') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Podaci o kupcu</h3>
        <span class="status-badge status-<?= $reservation['status'] ?>"><?= ucfirst($reservation['status']) ?></span>
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
    
    <div class="form-row">
        <div>
            <p><strong>Period:</strong> <?= formatDate($reservation['date_start']) ?> - <?= formatDate($reservation['date_end']) ?></p>
            <p><strong>Ukupno dana:</strong> <?= $reservation['total_days'] ?></p>
            <p><strong>Dostava:</strong> <?= ucfirst($reservation['delivery_option']) ?></p>
        </div>
        <div>
            <p><strong>Međuzbir:</strong> <?= formatPrice($reservation['subtotal']) ?></p>
            <?php if ($reservation['weekend_markup'] > 0): ?>
            <p><strong>Vikend doplata:</strong> +<?= formatPrice($reservation['weekend_markup']) ?></p>
            <?php endif; ?>
            <?php if ($reservation['discount'] > 0): ?>
            <p><strong>Popust:</strong> -<?= formatPrice($reservation['discount']) ?></p>
            <?php endif; ?>
            <p><strong>Dostava:</strong> <?= formatPrice($reservation['delivery_fee']) ?></p>
            <p style="font-size: 1.2em;"><strong>UKUPNO: <?= formatPrice($reservation['total']) ?></strong></p>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
