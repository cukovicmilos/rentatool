<?php
/**
 * Admin Tools Management
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
        redirect('admin/alati');
    }
    
    $postAction = post('_action', $action);
    
    // Delete image
    if ($postAction === 'delete_image') {
        $imageId = (int) post('image_id');
        $image = db()->fetch("SELECT * FROM tool_images WHERE id = ?", [$imageId]);
        if ($image) {
            deleteUpload('tools/' . $image['filename']);
            db()->execute("DELETE FROM tool_images WHERE id = ?", [$imageId]);
            flash('success', 'Slika je obrisana.');
        }
        redirect('admin/alati/izmeni/' . post('tool_id'));
    }
    
    // Set primary image
    if ($postAction === 'set_primary') {
        $imageId = (int) post('image_id');
        $toolId = (int) post('tool_id');
        db()->execute("UPDATE tool_images SET is_primary = 0 WHERE tool_id = ?", [$toolId]);
        db()->execute("UPDATE tool_images SET is_primary = 1 WHERE id = ?", [$imageId]);
        flash('success', 'Primarna slika je postavljena.');
        redirect('admin/alati/izmeni/' . $toolId);
    }
    
    // Save tool
    $name = trim(post('name'));
    $description = trim(post('description'));
    $shortDescription = trim(post('short_description'));
    $price24h = (float) post('price_24h');
    $deposit = (float) post('deposit', 0);
    $status = post('status', 'available');
    $featured = post('featured') ? 1 : 0;
    $categoryIds = post('categories', []);
    $specNames = post('spec_names', []);
    $specValues = post('spec_values', []);
    
    // Generate slug
    $slug = slugify($name);
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Naziv alata je obavezan.';
    }
    if ($price24h <= 0) {
        $errors[] = 'Cena mora biti veća od 0.';
    }
    
    // Check unique slug
    $existingSlug = db()->fetch("SELECT id FROM tools WHERE slug = ? AND id != ?", [$slug, $id]);
    if ($existingSlug) {
        $slug = $slug . '-' . time();
    }
    
    if (empty($errors)) {
        db()->beginTransaction();
        
        try {
            if ($action === 'dodaj') {
                $id = db()->insert(
                    "INSERT INTO tools (name, slug, description, short_description, price_24h, deposit, status, featured) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $slug, $description, $shortDescription, $price24h, $deposit, $status, $featured]
                );
            } else {
                db()->execute(
                    "UPDATE tools SET name = ?, slug = ?, description = ?, short_description = ?, 
                     price_24h = ?, deposit = ?, status = ?, featured = ?, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?",
                    [$name, $slug, $description, $shortDescription, $price24h, $deposit, $status, $featured, $id]
                );
            }
            
            // Update categories
            db()->execute("DELETE FROM tool_categories WHERE tool_id = ?", [$id]);
            foreach ($categoryIds as $catId) {
                db()->insert("INSERT INTO tool_categories (tool_id, category_id) VALUES (?, ?)", [$id, (int)$catId]);
            }
            
            // Update specifications
            db()->execute("DELETE FROM tool_specifications WHERE tool_id = ?", [$id]);
            foreach ($specNames as $i => $specName) {
                $specName = trim($specName);
                $specValue = trim($specValues[$i] ?? '');
                if (!empty($specName) && !empty($specValue)) {
                    db()->insert(
                        "INSERT INTO tool_specifications (tool_id, spec_name, spec_value, sort_order) VALUES (?, ?, ?, ?)",
                        [$id, $specName, $specValue, $i]
                    );
                }
            }
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = 'tools';
                if (!is_dir(UPLOADS_PATH . '/' . $uploadDir)) {
                    mkdir(UPLOADS_PATH . '/' . $uploadDir, 0755, true);
                }
                
                $currentImageCount = db()->fetchColumn("SELECT COUNT(*) FROM tool_images WHERE tool_id = ?", [$id]);
                
                foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$i],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]
                        ];
                        
                        $filename = uploadImage($file, $uploadDir);
                        if ($filename) {
                            $isPrimary = ($currentImageCount === 0 && $i === 0) ? 1 : 0;
                            db()->insert(
                                "INSERT INTO tool_images (tool_id, filename, sort_order, is_primary) VALUES (?, ?, ?, ?)",
                                [$id, basename($filename), $i, $isPrimary]
                            );
                            $currentImageCount++;
                        }
                    }
                }
            }
            
            db()->commit();
            flash('success', $action === 'dodaj' ? 'Alat je uspešno dodat.' : 'Alat je uspešno izmenjen.');
            redirect('admin/alati');
            
        } catch (Exception $e) {
            db()->rollback();
            $errors[] = 'Greška pri čuvanju: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'obrisi' && $id) {
    // Check if tool has reservations
    $resCount = db()->fetchColumn("SELECT COUNT(*) FROM reservation_items WHERE tool_id = ?", [$id]);
    
    if ($resCount > 0) {
        flash('error', 'Nije moguće obrisati alat koji ima rezervacije. Postavite status na "Neaktivan".');
    } else {
        // Delete images
        $images = db()->fetchAll("SELECT filename FROM tool_images WHERE tool_id = ?", [$id]);
        foreach ($images as $img) {
            deleteUpload('tools/' . $img['filename']);
        }
        
        db()->execute("DELETE FROM tools WHERE id = ?", [$id]);
        flash('success', 'Alat je uspešno obrisan.');
    }
    redirect('admin/alati');
}

// Get data for forms
$tool = null;
$toolCategories = [];
$toolSpecs = [];
$toolImages = [];

if ($action === 'izmeni' && $id) {
    $tool = db()->fetch("SELECT * FROM tools WHERE id = ?", [$id]);
    if (!$tool) {
        flash('error', 'Alat nije pronađen.');
        redirect('admin/alati');
    }
    
    $toolCategories = db()->fetchAll("SELECT category_id FROM tool_categories WHERE tool_id = ?", [$id]);
    $toolCategories = array_column($toolCategories, 'category_id');
    
    $toolSpecs = db()->fetchAll("SELECT * FROM tool_specifications WHERE tool_id = ? ORDER BY sort_order", [$id]);
    $toolImages = db()->fetchAll("SELECT * FROM tool_images WHERE tool_id = ? ORDER BY sort_order", [$id]);
}

// Get all categories for select
$allCategories = db()->fetchAll("SELECT id, name, parent_id FROM categories WHERE active = 1 ORDER BY sort_order, name");

// Get tools for list
$filterCategory = get('category', '');
$filterStatus = get('status', '');

$where = "1=1";
$params = [];

if ($filterCategory) {
    $where .= " AND t.id IN (SELECT tool_id FROM tool_categories WHERE category_id = ?)";
    $params[] = (int) $filterCategory;
}
if ($filterStatus) {
    $where .= " AND t.status = ?";
    $params[] = $filterStatus;
}

$tools = db()->fetchAll("
    SELECT t.*,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT GROUP_CONCAT(c.name, ', ') FROM categories c 
            JOIN tool_categories tc ON c.id = tc.category_id 
            WHERE tc.tool_id = t.id) as category_names
    FROM tools t
    WHERE {$where}
    ORDER BY t.created_at DESC
", $params);

$pageTitle = 'Alati - Admin';

ob_start();
?>

<?php if ($action === 'list'): ?>

<div class="admin-page-header">
    <h1>Alati (<?= count($tools) ?>)</h1>
    <a href="<?= url('admin/alati/dodaj') ?>" class="btn btn-primary">+ Dodaj alat</a>
</div>

<div class="admin-card">
    <form method="GET" action="<?= url('admin/alati') ?>" class="d-flex gap-2 flex-wrap mb-3">
        <select name="category" class="form-control" style="max-width: 200px;">
            <option value="">Sve kategorije</option>
            <?php foreach ($allCategories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                    <?= $cat['parent_id'] ? '-- ' : '' ?><?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="status" class="form-control" style="max-width: 150px;">
            <option value="">Svi statusi</option>
            <option value="available" <?= $filterStatus === 'available' ? 'selected' : '' ?>>Dostupan</option>
            <option value="rented" <?= $filterStatus === 'rented' ? 'selected' : '' ?>>Iznajmljen</option>
            <option value="maintenance" <?= $filterStatus === 'maintenance' ? 'selected' : '' ?>>Servis</option>
            <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Neaktivan</option>
        </select>
        
        <button type="submit" class="btn btn-secondary">Filtriraj</button>
        <?php if ($filterCategory || $filterStatus): ?>
            <a href="<?= url('admin/alati') ?>" class="btn btn-secondary">Resetuj</a>
        <?php endif; ?>
    </form>
    
    <?php if (empty($tools)): ?>
        <p class="text-muted">Nema alata.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Slika</th>
                        <th>Naziv</th>
                        <th>Kategorije</th>
                        <th>Cena/24h</th>
                        <th>Status</th>
                        <th class="actions">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tools as $t): ?>
                    <tr>
                        <td>
                            <?php if ($t['primary_image']): ?>
                                <img src="<?= upload('tools/' . $t['primary_image']) ?>" 
                                     alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <span style="color: var(--color-gray-400);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($t['name']) ?></strong>
                            <?php if ($t['featured']): ?>
                                <span class="status-badge badge-featured" style="margin-left: 4px;">★</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted"><?= e($t['slug']) ?></small>
                        </td>
                        <td><small><?= e($t['category_names'] ?? '-') ?></small></td>
                        <td><strong><?= formatPrice($t['price_24h']) ?></strong></td>
                        <td>
                            <span class="status-badge status-<?= $t['status'] ?>">
                                <?= ucfirst($t['status']) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?= url('alat/' . $t['slug']) ?>" class="btn btn-secondary btn-small" target="_blank">👁</a>
                            <a href="<?= url('admin/alati/izmeni/' . $t['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                            <a href="<?= url('admin/alati/obrisi/' . $t['id']) ?>" 
                               class="btn btn-danger btn-small" 
                               data-confirm="Da li ste sigurni da želite da obrišete ovaj alat?">Obriši</a>
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
    <h1><?= $action === 'dodaj' ? 'Dodaj alat' : 'Izmeni alat' ?></h1>
    <a href="<?= url('admin/alati') ?>" class="btn btn-secondary">← Nazad</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($toolImages)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Postojeće slike</h3>
    </div>
    <div class="d-flex flex-wrap">
        <?php foreach ($toolImages as $img): ?>
        <div class="image-preview">
            <img src="<?= upload('tools/' . $img['filename']) ?>" alt="">
            <?php if ($img['is_primary']): ?>
                <span style="position: absolute; bottom: 4px; left: 4px; background: var(--color-accent); 
                             padding: 2px 6px; border-radius: 2px; font-size: 10px;">Primarna</span>
            <?php endif; ?>
            <div style="position: absolute; top: -8px; right: -8px; display: flex; gap: 4px;">
                <?php if (!$img['is_primary']): ?>
                <form method="POST" style="margin: 0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="_action" value="set_primary">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <input type="hidden" name="tool_id" value="<?= $tool['id'] ?>">
                    <button type="submit" class="remove-btn" style="background: var(--color-success);" title="Postavi kao primarnu">★</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="margin: 0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="_action" value="delete_image">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <input type="hidden" name="tool_id" value="<?= $tool['id'] ?>">
                    <button type="submit" class="remove-btn" data-confirm="Obrisati sliku?">&times;</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Osnovni podaci</h3>
        </div>
        
        <div class="admin-form">
            <div class="form-group">
                <label for="name" class="form-label required">Naziv alata</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?= e($tool['name'] ?? post('name')) ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="short_description" class="form-label">Kratak opis</label>
                <input type="text" id="short_description" name="short_description" class="form-control" 
                       value="<?= e($tool['short_description'] ?? post('short_description')) ?>"
                       placeholder="Kratak opis za prikaz u listi (max 100 karaktera)">
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Detaljan opis</label>
                <textarea id="description" name="description" class="form-control" rows="5"><?= e($tool['description'] ?? post('description')) ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price_24h" class="form-label required">Cena za 24h (<?= CURRENCY_SYMBOL ?>)</label>
                    <input type="number" id="price_24h" name="price_24h" class="form-control" 
                           value="<?= e($tool['price_24h'] ?? post('price_24h')) ?>" 
                           step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="deposit" class="form-label">Depozit/Kaucija (<?= CURRENCY_SYMBOL ?>)</label>
                    <input type="number" id="deposit" name="deposit" class="form-control" 
                           value="<?= e($tool['deposit'] ?? post('deposit', 0)) ?>" 
                           step="0.01" min="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="available" <?= ($tool['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Dostupan</option>
                        <option value="rented" <?= ($tool['status'] ?? '') === 'rented' ? 'selected' : '' ?>>Iznajmljen</option>
                        <option value="maintenance" <?= ($tool['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>U servisu</option>
                        <option value="inactive" <?= ($tool['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Neaktivan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <label class="form-check">
                        <input type="checkbox" name="featured" value="1" <?= ($tool['featured'] ?? 0) ? 'checked' : '' ?>>
                        Istaknut alat (prikaži na vrhu)
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategorije</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($allCategories as $cat): ?>
                        <label class="form-check" style="margin: 0;">
                            <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                                <?= in_array($cat['id'], $toolCategories) ? 'checked' : '' ?>>
                            <?= $cat['parent_id'] ? '-- ' : '' ?><?= e($cat['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Dodaj slike</h3>
        </div>
        
        <div class="form-group">
            <label for="images" class="form-label">Dodaj slike</label>
            <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple>
            <p class="form-text">Dozvoljeni formati: JPG, PNG, WebP. Max veličina: 5MB po slici.</p>
        </div>
        <div id="imagePreview" class="d-flex flex-wrap"></div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Specifikacije</h3>
            <button type="button" id="addSpec" class="btn btn-secondary btn-small">+ Dodaj</button>
        </div>
        
        <div id="specList" class="spec-list">
            <?php 
            $specs = !empty($toolSpecs) ? $toolSpecs : [['spec_name' => '', 'spec_value' => '']];
            foreach ($specs as $spec): 
            ?>
            <div class="spec-row">
                <input type="text" name="spec_names[]" class="form-control" 
                       placeholder="Naziv (npr. Snaga)" value="<?= e($spec['spec_name']) ?>">
                <input type="text" name="spec_values[]" class="form-control" 
                       placeholder="Vrednost (npr. 1500W)" value="<?= e($spec['spec_value']) ?>">
                <button type="button" class="btn btn-danger btn-small remove-spec">&times;</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <p class="form-text mt-2">Predlozi: Snaga (W), Težina (kg), Napon (V), Broj obrtaja (min⁻¹), Prečnik diska (mm)</p>
    </div>
    
    <div class="admin-card">
        <div class="form-actions" style="border: none; margin: 0; padding: 0;">
            <button type="submit" class="btn btn-primary btn-large">
                <?= $action === 'dodaj' ? 'Dodaj alat' : 'Sačuvaj izmene' ?>
            </button>
            <a href="<?= url('admin/alati') ?>" class="btn btn-secondary">Otkaži</a>
        </div>
    </div>
</form>

<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/admin/layout.php';
