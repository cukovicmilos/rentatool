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
        generateSitemap();
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
            generateSitemap();
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

// Get all categories for list - parents and children separate for drag&drop
$categories = db()->fetchAll("
    SELECT c.*,
           p.name as parent_name,
           (SELECT COUNT(*) FROM tool_categories tc WHERE tc.category_id = c.id) as tool_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.name
");

// Build parent and children arrays for drag&drop
$parentCats = [];
$childCats = [];
foreach ($categories as $cat) {
    if ($cat['parent_id']) {
        $childCats[$cat['parent_id']][] = $cat;
    } else {
        $parentCats[] = $cat;
    }
}
// Sort parents by sort_order
usort($parentCats, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

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
        <p class="text-muted mb-3">Prevucite kategorije da promenite redosled. Promene se automatski čuvaju.</p>

        <div id="catSortParents" class="cat-sort-list">
            <?php foreach ($parentCats as $cat): ?>
            <div class="cat-sort-item" data-id="<?= $cat['id'] ?>" draggable="true">
                <div class="cat-sort-row">
                    <span class="cat-sort-handle" title="Prevucite">&#9776;</span>
                    <strong class="cat-sort-name"><?= e($cat['name']) ?></strong>
                    <code class="cat-sort-slug"><?= e($cat['slug']) ?></code>
                    <span class="cat-sort-count"><?= $cat['tool_count'] ?> alata</span>
                    <span class="status-badge status-<?= $cat['active'] ? 'available' : 'inactive' ?>">
                        <?= $cat['active'] ? 'Aktivna' : 'Neaktivna' ?>
                    </span>
                    <span class="cat-sort-actions">
                        <a href="<?= url('admin/kategorije/izmeni/' . $cat['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                        <a href="<?= url('admin/kategorije/obrisi/' . $cat['id']) ?>"
                           class="btn btn-danger btn-small"
                           data-confirm="Da li ste sigurni da želite da obrišete ovu kategoriju?">Obriši</a>
                    </span>
                </div>
                <?php if (!empty($childCats[$cat['id']])): ?>
                <div class="cat-sort-children" data-parent="<?= $cat['id'] ?>">
                    <?php foreach ($childCats[$cat['id']] as $child): ?>
                    <div class="cat-sort-item cat-sort-child" data-id="<?= $child['id'] ?>" draggable="true">
                        <div class="cat-sort-row">
                            <span class="cat-sort-handle" title="Prevucite">&#9776;</span>
                            <span class="cat-sort-name"><?= e($child['name']) ?></span>
                            <code class="cat-sort-slug"><?= e($child['slug']) ?></code>
                            <span class="cat-sort-count"><?= $child['tool_count'] ?> alata</span>
                            <span class="status-badge status-<?= $child['active'] ? 'available' : 'inactive' ?>">
                                <?= $child['active'] ? 'Aktivna' : 'Neaktivna' ?>
                            </span>
                            <span class="cat-sort-actions">
                                <a href="<?= url('admin/kategorije/izmeni/' . $child['id']) ?>" class="btn btn-secondary btn-small">Izmeni</a>
                                <a href="<?= url('admin/kategorije/obrisi/' . $child['id']) ?>"
                                   class="btn btn-danger btn-small"
                                   data-confirm="Da li ste sigurni da želite da obrišete ovu kategoriju?">Obriši</a>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="catSortStatus" class="cat-sort-status" style="display:none;"></div>
    <?php endif; ?>
</div>

<style>
.cat-sort-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.cat-sort-item {
    border: 1px solid var(--border-color);
    border-bottom: none;
    background: var(--color-white);
    transition: background 0.15s;
}
.cat-sort-item:last-child {
    border-bottom: 1px solid var(--border-color);
}
.cat-sort-item:first-child {
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}
.cat-sort-list > .cat-sort-item:last-child {
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}
.cat-sort-item.drag-over {
    border-top: 3px solid var(--color-accent);
}
.cat-sort-item.dragging {
    opacity: 0.4;
    background: var(--color-gray-100);
}
.cat-sort-row {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
}
.cat-sort-handle {
    cursor: grab;
    color: var(--color-gray-400);
    font-size: 18px;
    user-select: none;
    flex-shrink: 0;
    padding: 0 4px;
}
.cat-sort-handle:active {
    cursor: grabbing;
}
.cat-sort-name {
    flex: 1;
    min-width: 0;
}
.cat-sort-slug {
    color: var(--color-gray-500);
    font-size: var(--font-size-small);
}
.cat-sort-count {
    color: var(--color-gray-500);
    font-size: var(--font-size-small);
    white-space: nowrap;
}
.cat-sort-actions {
    display: flex;
    gap: var(--spacing-sm);
    flex-shrink: 0;
}
.cat-sort-children {
    margin-left: 32px;
    border-left: 3px solid var(--color-gray-200);
}
.cat-sort-child {
    border-left: none;
    background: var(--color-gray-100);
}
.cat-sort-child .cat-sort-name {
    font-weight: normal;
}
.cat-sort-status {
    margin-top: var(--spacing-md);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--border-radius);
    font-size: var(--font-size-small);
    text-align: center;
}
.cat-sort-status.success {
    background: #d4edda;
    color: #155724;
}
.cat-sort-status.error {
    background: #f8d7da;
    color: #721c24;
}
@media (max-width: 768px) {
    .cat-sort-row {
        flex-wrap: wrap;
        gap: var(--spacing-sm);
    }
    .cat-sort-slug {
        display: none;
    }
    .cat-sort-children {
        margin-left: 16px;
    }
}
</style>

<script>
(function() {
    const apiUrl = '<?= url('api/categories-reorder') ?>';

    function initDragDrop(container) {
        let dragItem = null;

        container.addEventListener('dragstart', function(e) {
            const item = e.target.closest('.cat-sort-item');
            if (!item || !container.contains(item)) return;
            dragItem = item;
            item.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.dataset.id);
        });

        container.addEventListener('dragend', function(e) {
            const item = e.target.closest('.cat-sort-item');
            if (item) item.classList.remove('dragging');
            container.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            dragItem = null;
        });

        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const target = e.target.closest('.cat-sort-item');
            if (!target || target === dragItem || !container.contains(target)) return;

            // Only allow reorder within same level
            if (target.parentElement !== dragItem.parentElement) return;

            container.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            target.classList.add('drag-over');
        });

        container.addEventListener('dragleave', function(e) {
            const target = e.target.closest('.cat-sort-item');
            if (target) target.classList.remove('drag-over');
        });

        container.addEventListener('drop', function(e) {
            e.preventDefault();
            const target = e.target.closest('.cat-sort-item');
            if (!target || target === dragItem || !container.contains(target)) return;
            if (target.parentElement !== dragItem.parentElement) return;

            target.classList.remove('drag-over');

            const parent = target.parentElement;
            const items = [...parent.children].filter(el => el.classList.contains('cat-sort-item'));
            const dragIndex = items.indexOf(dragItem);
            const targetIndex = items.indexOf(target);

            if (dragIndex < targetIndex) {
                parent.insertBefore(dragItem, target.nextSibling);
            } else {
                parent.insertBefore(dragItem, target);
            }

            saveOrder(parent);
        });
    }

    function saveOrder(listContainer) {
        const items = listContainer.querySelectorAll(':scope > .cat-sort-item');
        const order = [...items].map(item => parseInt(item.dataset.id));

        const status = document.getElementById('catSortStatus');
        status.style.display = 'block';
        status.className = 'cat-sort-status';
        status.textContent = 'Čuvanje...';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: order })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                status.className = 'cat-sort-status success';
                status.textContent = 'Redosled je sačuvan.';
            } else {
                status.className = 'cat-sort-status error';
                status.textContent = data.error || 'Greška pri čuvanju.';
            }
            setTimeout(() => { status.style.display = 'none'; }, 2000);
        })
        .catch(() => {
            status.className = 'cat-sort-status error';
            status.textContent = 'Greška pri čuvanju.';
            setTimeout(() => { status.style.display = 'none'; }, 2000);
        });
    }

    // Init for parent list
    const parentList = document.getElementById('catSortParents');
    if (parentList) {
        initDragDrop(parentList);

        // Init for each children group
        parentList.querySelectorAll('.cat-sort-children').forEach(function(childList) {
            initDragDrop(childList);
        });
    }
})();
</script>

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
