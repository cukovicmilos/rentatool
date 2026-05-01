<?php
/**
 * Search Results Page
 */

$query = trim(get('q', ''));
$tools = [];

if (strlen($query) >= 2) {
    $normalized = normalizeSerbian($query);
    $searchTerm = '%' . $normalized . '%';
    $nameNorm = sqlNormalizeSerbian('t.name');
    $shortNorm = sqlNormalizeSerbian('t.short_description');

    $tools = db()->fetchAll("
        SELECT t.*,
               (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM tools t
        WHERE t.status IN ('available', 'rented')
          AND ({$nameNorm} LIKE ? OR {$shortNorm} LIKE ?)
        ORDER BY
            CASE WHEN {$nameNorm} LIKE ? THEN 0 ELSE 1 END,
            t.featured DESC,
            t.name
    ", [$searchTerm, $searchTerm, $searchTerm]);
}

$pageTitle = 'Pretraga' . ($query ? ': ' . $query : '') . ' | ' . SITE_NAME;
$pageDescription = 'Rezultati pretrage alata za iznajmljivanje.' . ($query ? ' Tražili ste: ' . $query . '.' : '');
$bodyClass = 'search-page';

ob_start();
?>

<div class="page-header">
    <h1>Pretraga alata</h1>
    <?php if ($query): ?>
        <p class="text-muted">Rezultati za: <strong><?= e($query) ?></strong> (<?= count($tools) ?>)</p>
    <?php else: ?>
        <p class="text-muted">Unesite termin pretrage da biste pronašli alate.</p>
    <?php endif; ?>
</div>

<?php if ($query && empty($tools)): ?>
    <div class="alert alert-info">
        Nema rezultata za „<?= e($query) ?>“. Pokušajte sa drugačijim terminom.
    </div>
<?php elseif (!empty($tools)): ?>
    <div class="tools-grid">
        <?php foreach ($tools as $tool): ?>
            <?php include TEMPLATES_PATH . '/components/tool-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.search-page .page-header {
    margin-bottom: var(--spacing-xl);
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
