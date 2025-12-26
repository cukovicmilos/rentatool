<?php
/**
 * Header Template
 */

// Get cart item count from session
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<header class="site-header">
    <div class="header-container">
        
        <div class="logo">
            <a href="<?= url('') ?>">
                <span class="logo-icon">🔧</span>
                <span class="logo-text"><?= SITE_NAME ?></span>
            </a>
        </div>
        
        <button class="mobile-menu-toggle" aria-label="Meni" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <nav class="main-nav" id="mainNav">
            <ul class="nav-list">
                <li><a href="<?= url('') ?>" class="nav-link">Početna</a></li>
                <li><a href="<?= url('stranica/o-nama') ?>" class="nav-link">O servisu</a></li>
                <li><a href="<?= url('stranica/kontakt') ?>" class="nav-link">Kontakt</a></li>
                <li><a href="<?= url('stranica/uslovi-koriscenja') ?>" class="nav-link">Uslovi</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <a href="<?= url('korpa') ?>" class="cart-link">
                <span class="cart-icon">🛒</span>
                <span class="cart-text">Korpa</span>
                <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        
    </div>
</header>
