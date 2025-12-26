<?php
/**
 * Admin Static Pages Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$action = get('action', 'list');
$id = (int) get('id', 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Nevažeći zahtev.');
        redirect('admin/stranice');
    }
    
    $title = trim(post('title'));
    $content = post('content');
    $metaDescription = trim(post('meta_description'));
    $sortOrder = (int) post('sort_order', 0);
    $active = post('active') ? 1 : 0;
    
    $slug = slugify($title);
    
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Naslov stranice je obavezan.';
    }
    
    // Check unique slug
    $existingSlug = db()->fetch("SELECT id FROM pages WHERE slug = ? AND id != ?", [$slug, $id]);
    if ($existingSlug) {
        $slug = $slug . '-' . time();
    }
    
    if (empty($errors)) {
        if ($action === 'dodaj') {
            db()->insert(
                "INSERT INTO pages (title, slug, content, meta_description, sort_order, active) VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $slug, $content, $metaDescription, $sortOrder, $active]
            );
            flash('success', 'Stranica je uspešno dodata.');
        } else {
            db()->execute(
                "UPDATE pages SET title = ?, slug = ?, content = ?, meta_description = ?, sort_order = ?, active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$title, $slug, $content, $metaDescription, $sortOrder, $active, $id]
            );
            flash('success', 'Stranica je uspešno izmenjena.');
        }
        redirect('admin/stranice');
    }
}

// Handle delete
if ($action === 'obrisi' && $id) {
    db()->execute("DELETE FROM pages WHERE id = ?", [$id]);
    flash('success', 'Stranica je obrisana.');
    redirect('admin/stranice');
}

// Get page for edit
$page = null;
if ($action === 'izmeni' && $id) {
    $page = db()->fetch("SELECT * FROM pages WHERE id = ?", [$id]);
    if (!$page) {
        flash('error', 'Stranica nije pronađena.');
        redirect('admin/stranice');
    }
}

// Get all pages
$pages = db()->fetchAll("SELECT * FROM pages ORDER BY sort_order, title");

$pageTitle = 'Statičke stranice - Admin';

ob_start();
?>

<?php if ($action === 'list'): ?>

<div class="admin-page-header">
    <h1>Statičke stranice</h1>
    <a href="<?= url('admin/stranice/dodaj') ?>" class="btn btn-primary">+ Dodaj stranicu</a>
</div>

<div class="admin-card">
    <?php if (empty($pages)): ?>
        <p class="text-muted">Nema stranica.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Red.</th>
                        <th>Naslov</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th class="actions">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $p): ?>
                    <tr>
                        <td><?= $p['sort_order'] ?></td>
                        <td><strong><?= e($p['title']) ?></strong></td>
                        <td><code>/stranica/<?= e($p['slug']) ?></code></td>
                        <td>
                            <span class="status-badge status-<?= $p['active'] ? 'available' : 'inactive' ?>">
                                <?= $p['active'] ? 'Aktivna' : 'Neaktivna' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?= url('stranica/' . $p['slug']) ?>" class="btn btn-secondary btn-small" target="_blank">👁</a>
                            <a href="<?= url('admin/stranice/izmeni/' . $p['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                            <a href="<?= url('admin/stranice/obrisi/' . $p['id']) ?>" 
                               class="btn btn-danger btn-small" 
                               data-confirm="Da li ste sigurni?">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>

<div class="admin-page-header">
    <h1><?= $action === 'dodaj' ? 'Dodaj stranicu' : 'Izmeni stranicu' ?></h1>
    <a href="<?= url('admin/stranice') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card">
    <form method="POST" class="admin-form" style="max-width: none;">
        <?= csrfField() ?>
        
        <div class="form-group">
            <label for="title" class="form-label required">Naslov stranice</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?= e($page['title'] ?? post('title')) ?>" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="meta_description" class="form-label">Meta opis (SEO)</label>
            <input type="text" id="meta_description" name="meta_description" class="form-control" 
                   value="<?= e($page['meta_description'] ?? post('meta_description')) ?>"
                   placeholder="Kratak opis za pretraživače (max 160 karaktera)">
        </div>
        
        <div class="form-group">
            <label for="content" class="form-label">Sadržaj (HTML)</label>
            <textarea id="content" name="content" class="form-control" rows="15"><?= e($page['content'] ?? post('content')) ?></textarea>
            <p class="form-text">Možete koristiti HTML tagove za formatiranje.</p>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="sort_order" class="form-label">Redosled</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control" 
                       value="<?= e($page['sort_order'] ?? post('sort_order', 0)) ?>" min="0">
            </div>
            
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <label class="form-check">
                    <input type="checkbox" name="active" value="1" <?= ($page['active'] ?? 1) ? 'checked' : '' ?>>
                    Aktivna stranica
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'dodaj' ? 'Dodaj stranicu' : 'Sačuvaj izmene' ?>
            </button>
            <a href="<?= url('admin/stranice') ?>" class="btn btn-secondary">Otkaži</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
