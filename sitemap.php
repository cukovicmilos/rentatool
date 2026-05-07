<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$siteUrl = 'https://rentatool.in.rs' . BASE_URL;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $siteUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $siteUrl ?>/alati</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
<?php
$categories = db()->fetchAll("SELECT slug FROM categories WHERE active = 1 ORDER BY sort_order, name");
foreach ($categories as $cat):
?>
    <url>
        <loc><?= $siteUrl ?>/kategorija/<?= e($cat['slug']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
<?php
$tools = db()->fetchAll("SELECT slug, updated_at FROM tools WHERE status != 'inactive' ORDER BY name");
foreach ($tools as $tool):
    $lastMod = !empty($tool['updated_at']) ? date('Y-m-d', strtotime($tool['updated_at'])) : date('Y-m-d');
    $slug = $tool['slug'] ?: slugify($tool['name']);
?>
    <url>
        <loc><?= $siteUrl ?>/alat/<?= e($slug) ?></loc>
        <lastmod><?= $lastMod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
<?php endforeach; ?>
<?php
$pages = db()->fetchAll("SELECT slug, updated_at FROM pages WHERE active = 1 ORDER BY title");
foreach ($pages as $page):
    $lastMod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
?>
    <url>
        <loc><?= $siteUrl ?>/stranica/<?= e($page['slug']) ?></loc>
        <lastmod><?= $lastMod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; ?>
</urlset>
