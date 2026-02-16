<?php
/**
 * Admin Layout Template
 */

$pageTitle = $pageTitle ?? 'Admin - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><polygon points=%2250,5 63,38 98,38 69,59 80,95 50,72 20,95 31,59 2,38 37,38%22 fill=%22%23000%22/></svg>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
    
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-logo">
                <a href="<?= url('admin/') ?>">
                    🔧 <?= SITE_NAME ?> <span class="admin-badge">Admin</span>
                </a>
            </div>
            
            <nav class="admin-nav">
                <a href="<?= url('admin/') ?>" class="admin-nav-link">Dashboard</a>
                <a href="<?= url('admin/kategorije') ?>" class="admin-nav-link">Kategorije</a>
                <a href="<?= url('admin/alati') ?>" class="admin-nav-link">Alati</a>
                <a href="<?= url('admin/rezervacije') ?>" class="admin-nav-link">Rezervacije</a>
                <a href="<?= url('admin/blokirani-datumi') ?>" class="admin-nav-link">Blokirani datumi</a>
                <a href="<?= url('admin/stranice') ?>" class="admin-nav-link">Stranice</a>
            </nav>
            
            <div class="admin-user">
                <span class="admin-username">👤 <?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
                <a href="<?= url('admin/logout') ?>" class="btn btn-secondary btn-small">Odjava</a>
            </div>
        </div>
    </header>
    
    <main class="admin-main">
        <div class="admin-container">
            
            <?php 
            $flash = getFlash();
            if ($flash): 
            ?>
            <div class="alert alert-<?= e($flash['type']) ?> dismissible">
                <?= e($flash['message']) ?>
                <button type="button" class="alert-close">&times;</button>
            </div>
            <?php endif; ?>
            
            <?= $content ?? '' ?>
            
        </div>
    </main>
    
    <footer class="admin-footer">
        <div class="admin-container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?> Admin Panel</p>
        </div>
    </footer>
    
    <script src="<?= asset('js/main.js') ?>"></script>
    <script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
