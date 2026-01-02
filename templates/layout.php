<?php
/**
 * Base Layout Template
 * 
 * Usage:
 * $pageTitle = 'Page Title';
 * $pageDescription = 'Meta description';
 * $pageImage = '/uploads/tools/image.jpg'; // Optional OG image
 * $canonicalUrl = '/alat/1/bušilica'; // Optional canonical URL
 * $bodyClass = 'home-page';
 * $schemaData = [...]; // Optional Schema.org JSON-LD
 * ob_start();
 * // page content
 * $content = ob_get_clean();
 * include TEMPLATES_PATH . '/layout.php';
 */

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_DESCRIPTION;
$bodyClass = $bodyClass ?? '';
$showSidebar = $showSidebar ?? true;
$breadcrumbs = $breadcrumbs ?? [];
$pageImage = $pageImage ?? null;
$canonicalUrl = $canonicalUrl ?? null;
$schemaData = $schemaData ?? null;

// Build full URLs
$siteUrl = 'https://labubush.duckdns.org';
$fullCanonicalUrl = $canonicalUrl 
    ? $siteUrl . BASE_URL . $canonicalUrl 
    : $siteUrl . $_SERVER['REQUEST_URI'];
$fullImageUrl = $pageImage 
    ? $siteUrl . BASE_URL . $pageImage 
    : $siteUrl . BASE_URL . '/assets/images/og-default.jpg';
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?= e(SITE_NAME) ?>">
    <meta name="geo.region" content="RS-VO">
    <meta name="geo.placename" content="Subotica">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($fullCanonicalUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="<?= e($fullImageUrl) ?>">
    <meta property="og:locale" content="sr_RS">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($fullImageUrl) ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= e($fullCanonicalUrl) ?>">
    
    <title><?= e($pageTitle) ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    
    <?php if ($schemaData): ?>
    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    <?= json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    
    <?php if (!empty($extraCss)): ?>
    <?= $extraCss ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
    
    <?php include TEMPLATES_PATH . '/header.php'; ?>
    
    <main class="main-container">
        <div class="content-wrapper <?= $showSidebar ? 'with-sidebar' : 'full-width' ?>">
            
            <?php if ($showSidebar): ?>
            <aside class="sidebar">
                <?php include TEMPLATES_PATH . '/sidebar.php'; ?>
            </aside>
            <?php endif; ?>
            
            <div class="main-content">
                <?php if (!empty($breadcrumbs)): ?>
                <?php include TEMPLATES_PATH . '/components/breadcrumbs.php'; ?>
                <?php endif; ?>
                
                <?php 
                // Display flash messages
                $flash = getFlash();
                if ($flash): 
                ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
                <?php endif; ?>
                
                <?= $content ?? '' ?>
            </div>
            
        </div>
    </main>
    
    <?php include TEMPLATES_PATH . '/footer.php'; ?>
    
    <script src="<?= asset('js/main.js') ?>"></script>
    
    <?php if (!empty($extraJs)): ?>
    <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
