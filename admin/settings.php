<?php
/**
 * Admin Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/settings');
    }

    $eurRate = str_replace(',', '.', trim(post('eur_rate')));

    if (!is_numeric($eurRate) || (float) $eurRate <= 0) {
        flash('error', 'Kurs mora biti pozitivan broj.');
        redirect('admin/settings');
    }

    setSetting('eur_rate', (string) (float) $eurRate);
    flash('success', 'Podešavanja su sačuvana.');
    redirect('admin/settings');
}

$eurRate = getSetting('eur_rate', '118');

$pageTitle = 'Podešavanja - Admin';

ob_start();
?>

<div class="admin-page-header">
    <h1>Podešavanja</h1>
</div>

<div class="admin-card">
    <form method="POST" class="admin-form" style="max-width: none;">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="eur_rate" class="form-label required">Kurs (1 EUR u RSD)</label>
            <input type="number" id="eur_rate" name="eur_rate" class="form-control"
                   value="<?= e($eurRate) ?>" min="0.01" step="0.01" required>
            <p class="form-text">Koristi se za prikaz cena u dinarima na stranicama alata.</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Sačuvaj izmene</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
