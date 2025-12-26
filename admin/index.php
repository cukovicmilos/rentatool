<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Get statistics
$stats = [
    'tools' => db()->fetchColumn("SELECT COUNT(*) FROM tools"),
    'tools_available' => db()->fetchColumn("SELECT COUNT(*) FROM tools WHERE status = 'available'"),
    'categories' => db()->fetchColumn("SELECT COUNT(*) FROM categories WHERE active = 1"),
    'reservations' => db()->fetchColumn("SELECT COUNT(*) FROM reservations"),
    'reservations_pending' => db()->fetchColumn("SELECT COUNT(*) FROM reservations WHERE status = 'pending'"),
];

// Get recent reservations
$recentReservations = db()->fetchAll("
    SELECT * FROM reservations 
    ORDER BY created_at DESC 
    LIMIT 5
");

$pageTitle = 'Dashboard - Admin';

ob_start();
?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
</div>

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
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Poslednje rezervacije</h3>
        <a href="<?= url('admin/rezervacije') ?>" class="btn btn-secondary btn-small">Sve rezervacije →</a>
    </div>
    
    <?php if (empty($recentReservations)): ?>
        <p class="text-muted">Nema rezervacija.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Kupac</th>
                        <th>Datumi</th>
                        <th>Ukupno</th>
                        <th>Status</th>
                        <th>Datum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReservations as $res): ?>
                    <tr>
                        <td><strong><?= e($res['reservation_code']) ?></strong></td>
                        <td><?= e($res['customer_name']) ?></td>
                        <td><?= formatDate($res['date_start']) ?> - <?= formatDate($res['date_end']) ?></td>
                        <td><?= formatPrice($res['total']) ?></td>
                        <td><span class="status-badge status-<?= $res['status'] ?>"><?= ucfirst($res['status']) ?></span></td>
                        <td><?= formatDateTime($res['created_at']) ?></td>
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

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
