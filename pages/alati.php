<?php
/**
 * Tools Listing Page - Tools grouped by categories
 */

// Get all categories that have tools
$categories = db()->fetchAll("
    SELECT c.*, COUNT(tc.tool_id) as tool_count
    FROM categories c
    INNER JOIN tool_categories tc ON c.id = tc.category_id
    INNER JOIN tools t ON tc.tool_id = t.id AND t.status IN ('available', 'rented')
    WHERE c.active = 1
    GROUP BY c.id
    HAVING tool_count > 0
    ORDER BY c.sort_order, c.name
");

// Get all tools grouped by category
$toolsByCategory = [];
foreach ($categories as $category) {
    $toolsByCategory[$category['id']] = db()->fetchAll("
        SELECT t.*,
               (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM tools t
        INNER JOIN tool_categories tc ON t.id = tc.tool_id
        WHERE tc.category_id = ? AND t.status IN ('available', 'rented')
        ORDER BY t.featured DESC, t.name
    ", [$category['id']]);
}

// Page settings
$pageTitle = 'Svi alati | ' . SITE_NAME;
$pageDescription = 'Pregledajte sve alate dostupne za iznajmljivanje. Bušilice, brusilice, testera, kompresori i još mnogo toga.';
$bodyClass = 'tools-page';

ob_start();
?>

<div class="page-header">
    <h1>Svi alati</h1>
    <p class="text-muted">Pregledajte našu ponudu alata po kategorijama</p>
</div>

<?php if (empty($categories)): ?>
    <div class="alert alert-info">
        Trenutno nema dostupnih alata. Molimo posetite nas ponovo.
    </div>
<?php else: ?>
    
    <!-- Tools by category -->
    <?php foreach ($categories as $category): ?>
    <section class="category-section" id="category-<?= $category['id'] ?>">
        <div class="category-header">
            <h2><?= e($category['name']) ?></h2>
            <?php if (!empty($category['description'])): ?>
            <p class="text-muted"><?= e($category['description']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="tools-grid">
            <?php foreach ($toolsByCategory[$category['id']] as $tool): ?>
                <?php include TEMPLATES_PATH . '/components/tool-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    
<?php endif; ?>

<style>
.page-header {
    margin-bottom: var(--spacing-xl);
}

.category-section {
    margin-bottom: var(--spacing-xxl);
    scroll-margin-top: calc(var(--header-height) + var(--spacing-md));
}

.category-header {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--color-accent);
}

.category-header h2 {
    margin-bottom: var(--spacing-xs);
}

.category-header p {
    margin: 0;
}


</style>



<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
