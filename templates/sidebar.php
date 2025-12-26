<?php
/**
 * Sidebar Template - Category Navigation
 */

// Get categories from database
$categories = db()->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM tool_categories tc 
            JOIN tools t ON tc.tool_id = t.id 
            WHERE tc.category_id = c.id AND t.status = 'available') as tool_count
    FROM categories c 
    WHERE c.active = 1 AND c.parent_id IS NULL
    ORDER BY c.sort_order, c.name
");

// Get current category slug for active state
$currentCategorySlug = $currentCategorySlug ?? '';
?>

<div class="sidebar-section">
    <h3 class="sidebar-title">Kategorije</h3>
    <ul class="category-list">
        <li>
            <a href="<?= url('') ?>" class="category-link <?= empty($currentCategorySlug) ? 'active' : '' ?>">
                Svi alati
            </a>
        </li>
        <?php foreach ($categories as $category): ?>
        <li>
            <a href="<?= url('kategorija/' . $category['slug']) ?>" 
               class="category-link <?= $currentCategorySlug === $category['slug'] ? 'active' : '' ?>">
                <?= e($category['name']) ?>
                <?php if ($category['tool_count'] > 0): ?>
                <span class="category-count">(<?= $category['tool_count'] ?>)</span>
                <?php endif; ?>
            </a>
            
            <?php 
            // Get subcategories
            $subcategories = db()->fetchAll("
                SELECT * FROM categories 
                WHERE parent_id = ? AND active = 1 
                ORDER BY sort_order, name
            ", [$category['id']]);
            
            if (!empty($subcategories)): 
            ?>
            <ul class="subcategory-list">
                <?php foreach ($subcategories as $sub): ?>
                <li>
                    <a href="<?= url('kategorija/' . $sub['slug']) ?>" 
                       class="category-link <?= $currentCategorySlug === $sub['slug'] ? 'active' : '' ?>">
                        <?= e($sub['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="sidebar-section">
    <h3 class="sidebar-title">Kontakt</h3>
    <div class="contact-info">
        <p><strong>Tel:</strong> <?= SITE_PHONE ?></p>
        <p><strong>Email:</strong> <?= SITE_EMAIL ?></p>
    </div>
</div>

<div class="sidebar-section">
    <h3 class="sidebar-title">Radno vreme</h3>
    <div class="working-hours">
        <p>Pon - Pet: 08:00 - 18:00</p>
        <p>Sub: 08:00 - 14:00</p>
        <p>Ned: Zatvoreno</p>
    </div>
</div>
