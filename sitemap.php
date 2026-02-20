<?php
/**
 * Sitemap.xml Generator
 * 
 * Dynamically generates XML sitemap for search engines
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Set content type
header('Content-Type: application/xml; charset=utf-8');

$database = db();
$siteUrl = 'https://rentatool.in.rs' . BASE_URL;

// Start XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= $siteUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Categories -->
<?php
$categories = $database->fetchAll("SELECT * FROM categories WHERE active = 1 ORDER BY name");
foreach ($categories as $cat):
?>
    <url>
        <loc><?= $siteUrl ?>/kategorija/<?= $cat['id'] ?>/<?= slugify($cat['name']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
    
    <!-- Tools -->
<?php
$tools = $database->fetchAll("SELECT t.*, c.name as category_name FROM tools t 
                     JOIN categories c ON t.category_id = c.id 
                     WHERE t.active = 1 ORDER BY t.name");
foreach ($tools as $tool):
    $lastMod = !empty($tool['updated_at']) ? date('Y-m-d', strtotime($tool['updated_at'])) : date('Y-m-d');
?>
    <url>
        <loc><?= $siteUrl ?>/alat/<?= $tool['id'] ?>/<?= slugify($tool['name']) ?></loc>
        <lastmod><?= $lastMod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
<?php endforeach; ?>
    
    <!-- Static Pages -->
<?php
$pages = $database->fetchAll("SELECT * FROM pages WHERE active = 1 ORDER BY title");
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
