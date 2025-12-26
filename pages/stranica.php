<?php
/**
 * Static Page Display
 */

$slug = get('slug', '');

// Get page from database
$page = db()->fetch("SELECT * FROM pages WHERE slug = ? AND active = 1", [$slug]);

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Stranica nije pronađena';
    $content = '<div class="alert alert-error">Stranica nije pronađena.</div><p><a href="' . url('') . '">← Nazad na početnu</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

$pageTitle = $page['title'] . ' - ' . SITE_NAME;
$pageDescription = $page['meta_description'] ?? $page['title'];

$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
    ['title' => $page['title']]
];

ob_start();
?>

<div class="static-page">
    <h1><?= e($page['title']) ?></h1>
    
    <div class="page-content">
        <?= $page['content'] ?>
    </div>
</div>

<style>
.static-page {
    max-width: 800px;
}

.page-content {
    line-height: 1.8;
}

.page-content h2 {
    margin-top: var(--spacing-xl);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-accent);
}

.page-content p {
    margin-bottom: var(--spacing-md);
}

.page-content ul, .page-content ol {
    margin-bottom: var(--spacing-md);
    padding-left: var(--spacing-xl);
}

.page-content li {
    margin-bottom: var(--spacing-sm);
    list-style: disc;
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
