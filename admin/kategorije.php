<?php
/**
 * Admin Categories Management
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
        flash('error', 'Nevažeći zahtev. Pokušajte ponovo.');
        redirect('admin/kategorije');
    }
    
    $name = trim(post('name'));
    $parentId = post('parent_id') ? (int) post('parent_id') : null;
    $sortOrder = (int) post('sort_order', 0);
    $active = post('active') ? 1 : 0;
    
    // Generate slug
    $slug = slugify($name);
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Naziv kategorije je obavezan.';
    }
    
    // Check unique slug
    $existingSlug = db()->fetch("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
    if ($existingSlug) {
        $slug = $slug . '-' . time();
    }
    
    if (empty($errors)) {
        if ($action === 'dodaj') {
            db()->insert(
                "INSERT INTO categories (name, slug, parent_id, sort_order, active) VALUES (?, ?, ?, ?, ?)",
                [$name, $slug, $parentId, $sortOrder, $active]
            );
            flash('success', 'Kategorija je uspešno dodata.');
        } else {
            db()->execute(
                "UPDATE categories SET name = ?, slug = ?, parent_id = ?, sort_order = ?, active = ? WHERE id = ?",
                [$name, $slug, $parentId, $sortOrder, $active, $id]
            );
            flash('success', 'Kategorija je uspešno izmenjena.');
        }
        redirect('admin/kategorije');
    }
}

// Handle delete
if ($action === 'obrisi' && $id) {
    // Check if category has tools
    $toolCount = db()->fetchColumn("SELECT COUNT(*) FROM tool_categories WHERE category_id = ?", [$id]);
    
    if ($toolCount > 0) {
        flash('error', 'Nije moguće obrisati kategoriju koja ima alate.');
    } else {
        // Check for subcategories
        $subCount = db()->fetchColumn("SELECT COUNT(*) FROM categories WHERE parent_id = ?", [$id]);
        if ($subCount > 0) {
            flash('error', 'Nije moguće obrisati kategoriju koja ima podkategorije.');
        } else {
            db()->execute("DELETE FROM categories WHERE id = ?", [$id]);
            flash('success', 'Kategorija je uspešno obrisana.');
        }
    }
    redirect('admin/kategorije');
}

// Get data for forms
$category = null;
if ($action === 'izmeni' && $id) {
    $category = db()->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
    if (!$category) {
        flash('error', 'Kategorija nije pronađena.');
        redirect('admin/kategorije');
    }
}

// Get parent categories for dropdown
$parentCategories = db()->fetchAll("SELECT id, name FROM categories WHERE parent_id IS NULL AND id != ? ORDER BY sort_order, name", [$id]);

// Get all categories for list
$categories = db()->fetchAll("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM tool_categories tc WHERE tc.category_id = c.id) as tool_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.name
");

$pageTitle = 'Kategorije - Admin';

ob_start();
?>

<?php if ($action === 'list'): ?>

<div class="admin-page-header">
    <h1>Kategorije</h1>
    <a href="<?= url('admin/kategorije/dodaj') ?>" class="btn btn-primary">+ Dodaj kategoriju</a>
</div>

<div class="admin-card">
    <?php if (empty($categories)): ?>
        <p class="text-muted">Nema kategorija.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Redosled</th>
                        <th>Naziv</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Alata</th>
                        <th>Status</th>
                        <th class="actions">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['sort_order'] ?></td>
                        <td>
                            <?php if ($cat['parent_id']): ?>
                                <span style="color: var(--color-gray-400);">└</span>
                            <?php endif; ?>
                            <strong><?= e($cat['name']) ?></strong>
                        </td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td><?= e($cat['parent_name'] ?? '-') ?></td>
                        <td><?= $cat['tool_count'] ?></td>
                        <td>
                            <span class="status-badge status-<?= $cat['active'] ? 'available' : 'inactive' ?>">
                                <?= $cat['active'] ? 'Aktivna' : 'Neaktivna' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?= url('admin/kategorije/izmeni/' . $cat['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                            <a href="<?= url('admin/kategorije/obrisi/' . $cat['id']) ?>" 
                               class="btn btn-danger btn-small" 
                               data-confirm="Da li ste sigurni da želite da obrišete ovu kategoriju?">Obriši</a>
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
    <h1><?= $action === 'dodaj' ? 'Dodaj kategoriju' : 'Izmeni kategoriju' ?></h1>
    <a href="<?= url('admin/kategorije') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<div class="admin-card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form">
        <?= csrfField() ?>
        
        <div class="form-group">
            <label for="name" class="form-label required">Naziv kategorije</label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   class="form-control" 
                   value="<?= e($category['name'] ?? post('name')) ?>"
                   required 
                   autofocus>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="parent_id" class="form-label">Parent kategorija</label>
                <select id="parent_id" name="parent_id" class="form-control">
                    <option value="">-- Bez parenta (glavna kategorija) --</option>
                    <?php foreach ($parentCategories as $parent): ?>
                        <option value="<?= $parent['id'] ?>" 
                            <?= ($category['parent_id'] ?? post('parent_id')) == $parent['id'] ? 'selected' : '' ?>>
                            <?= e($parent['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sort_order" class="form-label">Redosled</label>
                <input type="number" 
                       id="sort_order" 
                       name="sort_order" 
                       class="form-control" 
                       value="<?= e($category['sort_order'] ?? post('sort_order', 0)) ?>"
                       min="0">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" 
                       name="active" 
                       value="1"
                       <?= ($category['active'] ?? 1) ? 'checked' : '' ?>>
                Aktivna kategorija
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'dodaj' ? 'Dodaj kategoriju' : 'Sačuvaj izmene' ?>
            </button>
            <a href="<?= url('admin/kategorije') ?>" class="btn btn-secondary">Otkaži</a>
        </div>
    </form>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
