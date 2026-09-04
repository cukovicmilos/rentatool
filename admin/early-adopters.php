<?php
/**
 * Admin - Early Adopters Management
 *
 * View and manage emails collected from the "Rent your tool" landing page.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$action = get('action', 'list');
$id = (int) get('id', 0);

// Handle delete
if ($action === 'obrisi' && $id) {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/early-adopters');
    }

    db()->execute("DELETE FROM early_adopters WHERE id = ?", [$id]);
    flash('success', 'Email je obrisan.');
    redirect('admin/early-adopters');
}

// Pagination
$page = max(1, (int) get('page', 1));
$perPage = 50;
$total = db()->fetchColumn("SELECT COUNT(*) FROM early_adopters");
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$earlyAdopters = db()->fetchAll("
    SELECT * FROM early_adopters
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset]);

$pageTitle = 'Zainteresovani za iznajmljivanje - Admin';

ob_start();
?>

<div class="admin-page-header">
    <h1>Zainteresovani za „Iznajmi svoj alat i zaradi"</h1>
    <a href="<?= url('admin/') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Prikupljene email adrese (<?= $total ?>)</h3>
    </div>

    <?php if (empty($earlyAdopters)): ?>
        <p class="text-muted">Još uvek nema prijavljenih korisnika.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>IP adresa</th>
                        <th>Datum prijave</th>
                        <th class="actions">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($earlyAdopters as $adopter): ?>
                    <tr>
                        <td><strong><?= e($adopter['email']) ?></strong></td>
                        <td><small><?= e($adopter['ip_address'] ?? '-') ?></small></td>
                        <td><small><?= formatDateTime($adopter['created_at']) ?></small></td>
                        <td class="actions">
                            <a href="mailto:<?= e($adopter['email']) ?>" class="btn btn-secondary btn-small">Email</a>
                            <form method="POST" action="<?= url('admin/early-adopters/obrisi/' . $adopter['id']) ?>" style="display:inline">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-danger btn-small" data-confirm="Obrisati email <?= e($adopter['email']) ?>?">Obriši</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="<?= url('admin/early-adopters?page=' . ($page - 1)) ?>" class="btn btn-secondary btn-small">← Prethodna</a>
            <?php endif; ?>
            <span class="pagination-info">Strana <?= $page ?> od <?= $totalPages ?> (<?= $total ?> prijava)</span>
            <?php if ($page < $totalPages): ?>
            <a href="<?= url('admin/early-adopters?page=' . ($page + 1)) ?>" class="btn btn-secondary btn-small">Sledeća →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
