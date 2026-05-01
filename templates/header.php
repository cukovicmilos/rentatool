<?php
/**
 * Header Template
 */

// Get cart item count from session
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Get categories for mega menu
$menuCategories = db()->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM tool_categories tc 
            JOIN tools t ON tc.tool_id = t.id 
            WHERE tc.category_id = c.id AND t.status = 'available') as tool_count
    FROM categories c 
    WHERE c.active = 1 AND c.parent_id IS NULL
    ORDER BY c.sort_order, c.name
");
?>
<header class="site-header">
    <div class="header-container">
        
        <div class="logo">
            <a href="<?= url('') ?>">
                <img src="<?= asset('images/rent-a-tool-logo-horizontal.svg') ?>" 
                     alt="<?= SITE_NAME ?>" 
                     class="logo-img"
                     width="160"
                     height="40">
            </a>
        </div>
        
        <nav class="main-nav" id="mainNav">
            <ul class="nav-list">
                <li><a href="<?= url('') ?>" class="nav-link">Početna</a></li>
                <li class="nav-item-dropdown">
                    <a href="<?= url('alati') ?>" class="nav-link nav-link-dropdown">
                        Alati
                        <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <?php if (!empty($menuCategories)): ?>
                    <div class="mega-menu">
                        <div class="mega-menu-content">
                            <?php foreach ($menuCategories as $cat): ?>
                            <div class="mega-menu-column">
                                <a href="<?= url('kategorija/' . $cat['slug']) ?>" class="mega-menu-title">
                                    <?= e($cat['name']) ?>
                                    <?php if ($cat['tool_count'] > 0): ?>
                                    <span class="mega-menu-count">(<?= $cat['tool_count'] ?>)</span>
                                    <?php endif; ?>
                                </a>
                                <?php 
                                // Get subcategories
                                $subcats = db()->fetchAll("
                                    SELECT * FROM categories 
                                    WHERE parent_id = ? AND active = 1 
                                    ORDER BY sort_order, name
                                ", [$cat['id']]);
                                if (!empty($subcats)): 
                                ?>
                                <ul class="mega-menu-links">
                                    <?php foreach ($subcats as $sub): ?>
                                    <li><a href="<?= url('kategorija/' . $sub['slug']) ?>"><?= e($sub['name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <div class="mega-menu-column mega-menu-cta">
                                <a href="<?= url('alati') ?>" class="btn btn-primary">Svi alati →</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </li>
                <li><a href="<?= url('stranica/o-nama') ?>" class="nav-link">O servisu</a></li>
                <li><a href="<?= url('stranica/kontakt') ?>" class="nav-link">Kontakt</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <div class="header-search">
                <form action="<?= url('pretraga') ?>" method="GET" class="search-form" autocomplete="off">
                    <input type="search" name="q" id="searchInput" class="search-input" placeholder="Pretraži alate..." aria-label="Pretraži alate" minlength="2" required>
                    <button type="submit" class="search-submit" aria-label="Pretraži">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M11.7422 10.3439C12.5329 9.2673 13 7.9382 13 6.5C13 2.91015 10.0899 0 6.5 0C2.91015 0 0 2.91015 0 6.5C0 10.0899 2.91015 13 6.5 13C7.9382 13 9.2673 12.5329 10.3439 11.7422L14.1464 15.1464L15 14.2929L11.7422 10.3439ZM6.5 11.5C3.73858 11.5 1.5 9.26142 1.5 6.5C1.5 3.73858 3.73858 1.5 6.5 1.5C9.26142 1.5 11.5 3.73858 11.5 6.5C11.5 9.26142 9.26142 11.5 6.5 11.5Z"/>
                        </svg>
                    </button>
                    <div class="search-dropdown" id="searchDropdown" hidden></div>
                </form>
            </div>
            
            <button class="mobile-search-toggle" id="mobileSearchToggle" aria-label="Pretraži" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M11.7422 10.3439C12.5329 9.2673 13 7.9382 13 6.5C13 2.91015 10.0899 0 6.5 0C2.91015 0 0 2.91015 0 6.5C0 10.0899 2.91015 13 6.5 13C7.9382 13 9.2673 12.5329 10.3439 11.7422L14.1464 15.1464L15 14.2929L11.7422 10.3439ZM6.5 11.5C3.73858 11.5 1.5 9.26142 1.5 6.5C1.5 3.73858 3.73858 1.5 6.5 1.5C9.26142 1.5 11.5 3.73858 11.5 6.5C11.5 9.26142 9.26142 11.5 6.5 11.5Z"/>
                </svg>
            </button>
            
            <a href="<?= url('korpa') ?>" class="cart-link" aria-label="Korpa<?= $cartCount > 0 ? ', ' . $cartCount . ' artikala' : ', prazna' ?>">
                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                <span class="cart-text">Korpa</span>
                <?php if ($cartCount > 0): ?>
                <span class="cart-count" aria-hidden="true"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            
            <button class="mobile-menu-toggle" aria-label="Otvori meni" aria-expanded="false" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
    </div>
    
    <div class="mobile-search-bar" id="mobileSearchBar" hidden>
        <div class="header-container">
            <form action="<?= url('pretraga') ?>" method="GET" class="search-form" autocomplete="off" style="width:100%">
                <input type="search" name="q" id="mobileSearchInput" class="search-input" placeholder="Pretraži alate..." aria-label="Pretraži alate" minlength="2" required>
                <button type="submit" class="search-submit" aria-label="Pretraži">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M11.7422 10.3439C12.5329 9.2673 13 7.9382 13 6.5C13 2.91015 10.0899 0 6.5 0C2.91015 0 0 2.91015 0 6.5C0 10.0899 2.91015 13 6.5 13C7.9382 13 9.2673 12.5329 10.3439 11.7422L14.1464 15.1464L15 14.2929L11.7422 10.3439ZM6.5 11.5C3.73858 11.5 1.5 9.26142 1.5 6.5C1.5 3.73858 3.73858 1.5 6.5 1.5C9.26142 1.5 11.5 3.73858 11.5 6.5C11.5 9.26142 9.26142 11.5 6.5 11.5Z"/>
                    </svg>
                </button>
                <div class="search-dropdown" id="mobileSearchDropdown" hidden></div>
            </form>
        </div>
    </div>
</header>
