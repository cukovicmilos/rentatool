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
?>
<nav class="breadcrumbs" aria-label="Navigacija">
    <ol class="breadcrumb-list">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <li class="breadcrumb-item <?= $index === count($breadcrumbs) - 1 ? 'active' : '' ?>">
            <?php if (isset($crumb['url']) && $index !== count($breadcrumbs) - 1): ?>
                <a href="<?= e($crumb['url']) ?>"><?= e($crumb['title']) ?></a>
            <?php else: ?>
                <span><?= e($crumb['title']) ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
