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
                <img src="<?= asset('images/rent-a-tool-logo-horizontal.svg') ?>" alt="<?= SITE_NAME ?>" class="logo-img">
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
                <li><a href="<?= url('alati') ?>" class="nav-link">Alati</a></li>
                <li><a href="<?= url('stranica/o-nama') ?>" class="nav-link">O servisu</a></li>
                <li><a href="<?= url('stranica/kontakt') ?>" class="nav-link">Kontakt</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <a href="<?= url('korpa') ?>" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-text">Korpa</span>
                <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        
    </div>
</header>
