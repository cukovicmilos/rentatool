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
    
    <!-- Preload critical resources for LCP optimization -->
    <link rel="preload" href="<?= asset('css/style.min.css') ?>" as="style">
    <link rel="preload" href="<?= asset('fonts/fontawesome/fa-solid-900.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <?php if ($bodyClass === 'promo-page'): ?>
    <!-- Preload LCP image for promo page -->
    <link rel="preload" href="<?= asset('images/rent-a-tool-logo-full.svg') ?>" as="image" fetchpriority="high">
    <link rel="preload" href="<?= asset('css/promo.min.css') ?>" as="style">
    <?php endif; ?>
    
    <!-- Critical CSS - Inline for faster FCP -->
    <style>
    /* Critical CSS - Above the fold styles for ALL pages */
    :root{--color-white:#FFF;--color-black:#000;--color-accent:#D90060;--color-accent-hover:#B8004F;--color-accent-light:#FFE0EC;--color-gray-100:#F5F5F5;--color-gray-200:#EEE;--color-gray-300:#CCC;--color-gray-400:#999;--color-gray-500:#666;--color-gray-600:#333;--font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;--font-size-base:16px;--font-size-small:14px;--font-size-large:18px;--font-size-h1:28px;--font-size-h2:24px;--spacing-xs:4px;--spacing-sm:8px;--spacing-md:16px;--spacing-lg:24px;--spacing-xl:32px;--spacing-xxl:48px;--container-max:1200px;--sidebar-width:220px;--header-height:60px;--border-radius:4px;--border-color:var(--color-gray-300)}*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}html{font-size:var(--font-size-base);line-height:1.5}body{font-family:var(--font-family);color:var(--color-black);background-color:var(--color-white);min-height:100vh;display:flex;flex-direction:column}img{display:block;max-width:100%;height:auto}a{color:inherit;text-decoration:none}ul{list-style:none}h1,h2,h3{font-weight:600;line-height:1.3;margin-bottom:var(--spacing-md)}h1{font-size:var(--font-size-h1)}h2{font-size:var(--font-size-h2)}
    /* Header */
    .site-header{background-color:var(--color-black);color:var(--color-white);height:var(--header-height);position:sticky;top:0;z-index:100}.header-container{max-width:var(--container-max);margin:0 auto;padding:0 var(--spacing-md);height:100%;display:flex;align-items:center;justify-content:space-between;gap:var(--spacing-lg)}.logo a{display:flex;align-items:center}.logo-img{height:40px;width:auto}.main-nav{flex:1}.nav-list{display:flex;gap:var(--spacing-lg);align-items:center}.nav-link{color:var(--color-white);padding:var(--spacing-sm) 0}.nav-item-dropdown{position:static}.nav-link-dropdown{display:inline-flex;align-items:center;gap:var(--spacing-xs)}.dropdown-arrow{width:12px;height:12px;transition:transform 0.2s}.header-actions{display:flex;align-items:center;gap:var(--spacing-sm)}.cart-link{display:flex;align-items:center;gap:4px;padding:var(--spacing-sm) var(--spacing-md);background:var(--color-accent);color:var(--color-white);border-radius:var(--border-radius);font-weight:600}.mobile-menu-toggle{display:none;flex-direction:column;justify-content:center;gap:4px;width:30px;height:30px;background:none;border:none;cursor:pointer;padding:0}.mobile-menu-toggle span{display:block;width:100%;height:3px;background:var(--color-white)}
    /* Layout */
    .main-container{flex:1;width:100%;max-width:var(--container-max);margin:0 auto;padding:var(--spacing-md)}.content-wrapper{display:flex;gap:var(--spacing-lg)}.content-wrapper.with-sidebar .sidebar{flex:0 0 var(--sidebar-width)}.content-wrapper.with-sidebar .main-content{flex:1;min-width:0}.content-wrapper.full-width .main-content{width:100%}
    /* Sidebar */
    .sidebar-section{margin-bottom:var(--spacing-lg);padding-bottom:var(--spacing-lg);border-bottom:1px solid var(--border-color)}.sidebar-title{font-size:var(--font-size-base);text-transform:uppercase;letter-spacing:.5px;margin-bottom:var(--spacing-md);padding-bottom:var(--spacing-sm);border-bottom:2px solid var(--color-accent)}.category-list{display:flex;flex-direction:column;gap:var(--spacing-xs)}.category-link{display:block;padding:var(--spacing-sm) 0;color:var(--color-gray-600);border-bottom:1px dotted var(--border-color)}
    /* Tool cards grid */
    .tools-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:var(--spacing-lg)}.tool-card{position:relative;background:var(--color-white);border:1px solid var(--border-color);border-radius:var(--border-radius);overflow:visible;margin-top:12px}.tool-card-image{position:relative;aspect-ratio:4/3;background:var(--color-gray-100)}.tool-card-image img{width:100%;height:100%;object-fit:contain}.tool-card-content{padding:var(--spacing-md)}
    /* Buttons */
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:var(--spacing-sm);padding:var(--spacing-sm) var(--spacing-lg);font-size:var(--font-size-base);font-weight:600;border:2px solid transparent;border-radius:var(--border-radius);cursor:pointer}.btn-primary{background:var(--color-accent);color:var(--color-white);border-color:var(--color-accent)}.btn-secondary{background:transparent;color:var(--color-gray-600);border-color:var(--color-gray-300)}
    /* Font fallback to prevent CLS */
    .fa,.fas,.far,.fab{font-family:var(--font-family);font-size:inherit}
    <?php if ($bodyClass === 'promo-page'): ?>
    /* Promo page specific critical CSS */
    .promo-page .main-container{max-width:100%;padding:0}.promo-hero{background:linear-gradient(135deg,var(--color-black) 0%,#1a1a1a 50%,#2d2d2d 100%);color:var(--color-white);padding:var(--spacing-xxl) var(--spacing-md);min-height:500px;display:flex;align-items:center}.promo-hero-content{max-width:var(--container-max);margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-xxl);align-items:center}.promo-hero-text{min-height:280px}.promo-social-proof{display:inline-flex;align-items:center;gap:var(--spacing-sm);background:rgba(255,215,0,.15);padding:4px var(--spacing-md);border-radius:50px;margin-bottom:var(--spacing-lg);min-height:32px}.promo-headline{font-size:clamp(28px,5vw,48px);font-weight:700;line-height:1.2;margin-bottom:var(--spacing-lg);color:var(--color-white)}.hero-logo-container{width:100%;max-width:520px;display:flex;justify-content:center;align-items:center}.hero-logo{width:100%;height:auto;max-width:455px}
    <?php endif; ?>
    @media(max-width:768px){.mobile-menu-toggle{display:flex;margin-right:calc(-1 * var(--spacing-sm))}.main-nav{display:none;position:absolute;top:var(--header-height);left:0;right:0;background:var(--color-black);padding:0;max-height:calc(100vh - var(--header-height));overflow-y:auto;-webkit-overflow-scrolling:touch}.main-nav.open{display:block}.nav-list{flex-direction:column;gap:0}.nav-link{display:flex;align-items:center;justify-content:center;padding:16px var(--spacing-lg);border-bottom:1px solid rgba(255,255,255,0.1);font-size:18px;min-height:56px;-webkit-tap-highlight-color:transparent}.nav-link:active{background:rgba(255,255,255,0.08)}.nav-list{text-align:center}.nav-list>li{text-align:center}.nav-link-dropdown{display:inline-flex;align-items:center;gap:var(--spacing-sm)}.cart-text{display:none}.content-wrapper{flex-direction:column}.content-wrapper.with-sidebar .sidebar{flex:none;width:100%;order:2;margin-top:var(--spacing-xl)}.tools-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--spacing-md)}.promo-hero-content{grid-template-columns:1fr;text-align:center}.promo-hero-visual{order:-1}.hero-logo-container{max-width:280px;margin:0 auto}}
    </style>

    <!-- Font Awesome - Inlined to eliminate render-blocking request -->
    <style>
    @font-face{font-family:"Font Awesome 6 Free";font-style:normal;font-weight:900;font-display:swap;src:url(/rentatool/assets/fonts/fontawesome/fa-solid-900.woff2) format("woff2")}
    .fa,.fas,.far,.fab{-moz-osx-font-smoothing:grayscale;-webkit-font-smoothing:antialiased;display:inline-block;font-style:normal;font-variant:normal;line-height:1;text-rendering:auto}
    .fa,.fas{font-family:"Font Awesome 6 Free";font-weight:900}
    .fa-award:before{content:"\f559"}.fa-calendar-alt:before,.fa-calendar-days:before{content:"\f073"}.fa-check-circle:before,.fa-circle-check:before{content:"\f058"}.fa-clock:before{content:"\f017"}.fa-comments:before{content:"\f086"}.fa-globe:before{content:"\f0ac"}.fa-headset:before{content:"\f590"}.fa-piggy-bank:before{content:"\f4d3"}.fa-shield-alt:before,.fa-shield-halved:before{content:"\f3ed"}.fa-shopping-cart:before,.fa-cart-shopping:before{content:"\f07a"}.fa-toolbox:before{content:"\f552"}.fa-truck:before{content:"\f0d1"}.fa-wrench:before{content:"\f0ad"}
    </style>

    <!-- Preconnect for YouTube thumbnails -->
    <link rel="preconnect" href="https://i.ytimg.com" crossorigin>
    
    <!-- Main stylesheet - defer non-critical CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.min.css') ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= asset('css/style.min.css') ?>"></noscript>
    
    <!-- Organization Schema (Global) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "@id": "<?= $siteUrl ?>/#organization",
        "name": "<?= e(SITE_NAME) ?>",
        "description": "<?= e(SITE_DESCRIPTION) ?>",
        "url": "<?= $siteUrl . BASE_URL ?>",
        "logo": "<?= $siteUrl . BASE_URL ?>/assets/images/rent-a-tool-logo-full.svg",
        "image": "<?= $siteUrl . BASE_URL ?>/assets/images/og-default.jpg",
        "telephone": "<?= e(SITE_PHONE) ?>",
        "email": "<?= e(SITE_EMAIL) ?>",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Subotica",
            "addressRegion": "Vojvodina",
            "addressCountry": "RS"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": "46.1000",
            "longitude": "19.6667"
        },
        "priceRange": "€",
        "openingHoursSpecification": [
            {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                "opens": "08:00",
                "closes": "18:00"
            },
            {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": "Saturday",
                "opens": "09:00",
                "closes": "14:00"
            }
        ],
        "sameAs": []
    }
    </script>

    <?php if ($schemaData): ?>
    <!-- Page-specific Schema.org JSON-LD -->
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
    
    <script src="<?= asset('js/main.js') ?>" defer></script>
    
    <?php if (!empty($extraJs)): ?>
    <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
