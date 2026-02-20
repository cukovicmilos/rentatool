<?php
/**
 * Category Page - Tools filtered by category
 */

$slug = get('slug', '');

// Get category
$category = db()->fetch("SELECT * FROM categories WHERE slug = ? AND active = 1", [$slug]);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Kategorija nije pronađena';
    $content = '<div class="alert alert-error">Kategorija nije pronađena.</div><p><a href="' . url('') . '">← Nazad na početnu</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// Get parent category if exists
$parentCategory = null;
if ($category['parent_id']) {
    $parentCategory = db()->fetch("SELECT * FROM categories WHERE id = ?", [$category['parent_id']]);
}

// Get subcategories
$subcategories = db()->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM tool_categories tc 
            JOIN tools t ON tc.tool_id = t.id 
            WHERE tc.category_id = c.id AND t.status = 'available') as tool_count
    FROM categories c 
    WHERE c.parent_id = ? AND c.active = 1 
    ORDER BY c.sort_order, c.name
", [$category['id']]);

// Get tools in this category
$tools = db()->fetchAll("
    SELECT t.*,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t
    JOIN tool_categories tc ON t.id = tc.tool_id
    WHERE tc.category_id = ? AND t.status IN ('available', 'rented')
    ORDER BY t.featured DESC, t.name
", [$category['id']]);

// Page settings
$pageTitle = $category['name'] . ' - ' . SITE_NAME;
$pageDescription = $category['description'] ?? 'Iznajmljivanje ' . $category['name'] . ' u Subotici';
$currentCategorySlug = $slug;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
    ['title' => 'Svi alati', 'url' => url('alati')],
];
if ($parentCategory) {
    $breadcrumbs[] = ['title' => $parentCategory['name'], 'url' => url('kategorija/' . $parentCategory['slug'])];
}
$breadcrumbs[] = ['title' => $category['name']];

ob_start();
?>

<div class="page-header">
    <h1><?= e($category['name']) ?></h1>
    <?php if ($category['description']): ?>
    <p class="text-muted"><?= e($category['description']) ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($subcategories)): ?>
<div class="subcategories-bar mb-3">
    <strong>Podkategorije:</strong>
    <?php foreach ($subcategories as $sub): ?>
        <a href="<?= url('kategorija/' . $sub['slug']) ?>" class="btn btn-secondary btn-small">
            <?= e($sub['name']) ?>
            <?php if ($sub['tool_count'] > 0): ?>
            <span>(<?= $sub['tool_count'] ?>)</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($tools)): ?>
    <div class="alert alert-info">
        Nema alata u ovoj kategoriji.
    </div>
<?php else: ?>
    <p class="text-muted mb-2">Pronađeno alata: <?= count($tools) ?></p>
    <div class="tools-grid">
        <?php foreach ($tools as $tool): ?>
            <?php include TEMPLATES_PATH . '/components/tool-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.subcategories-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--spacing-sm);
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
