<?php
/**
 * Breadcrumbs Component
 *
 * Usage:
 * $breadcrumbs = [
 *     ['title' => 'Početna', 'url' => url('')],
 *     ['title' => 'Kategorija', 'url' => url('kategorija/busilice')],
 *     ['title' => 'Alat'] // last item - no url
 * ];
 */

if (empty($breadcrumbs)) return;

// Build BreadcrumbList Schema
$siteUrl = 'https://labubush.duckdns.org';
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => []
];

foreach ($breadcrumbs as $index => $crumb) {
    $item = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $crumb['title']
    ];

    // Add URL if available (not for last item)
    if (isset($crumb['url'])) {
        $item['item'] = $siteUrl . $crumb['url'];
    }

    $breadcrumbSchema['itemListElement'][] = $item;
}
?>

<!-- BreadcrumbList Schema -->
<script type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<nav class="breadcrumbs" aria-label="Navigacija">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <li class="breadcrumb-item <?= $index === count($breadcrumbs) - 1 ? 'active' : '' ?>"
            itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"
            <?= $index === count($breadcrumbs) - 1 ? 'aria-current="page"' : '' ?>>
            <?php if (isset($crumb['url']) && $index !== count($breadcrumbs) - 1): ?>
                <a href="<?= e($crumb['url']) ?>" itemprop="item">
                    <span itemprop="name"><?= e($crumb['title']) ?></span>
                </a>
            <?php else: ?>
                <span itemprop="name"><?= e($crumb['title']) ?></span>
            <?php endif; ?>
            <meta itemprop="position" content="<?= $index + 1 ?>">
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
