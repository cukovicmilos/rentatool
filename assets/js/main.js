/**
 * Rent a Tool - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile menu toggle
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            const isOpen = mainNav.classList.toggle('open');
            menuToggle.classList.toggle('open', isOpen);
            menuToggle.setAttribute('aria-expanded', isOpen);
            menuToggle.setAttribute('aria-label', isOpen ? 'Zatvori meni' : 'Otvori meni');
        });
    }
    
    // Mobile dropdown toggle for mega menu
    const dropdownItems = document.querySelectorAll('.nav-item-dropdown');
    const isMobile = function() {
        return window.innerWidth <= 768;
    };
    
    dropdownItems.forEach(function(item) {
        const link = item.querySelector('.nav-link-dropdown');
        
        if (link) {
            link.addEventListener('click', function(e) {
                // Only prevent default on mobile
                if (isMobile()) {
                    e.preventDefault();
                    
                    // Close other dropdowns
                    dropdownItems.forEach(function(other) {
                        if (other !== item) {
                            other.classList.remove('open');
                        }
                    });
                    
                    // Toggle this dropdown
                    item.classList.toggle('open');
                }
            });
        }
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (mainNav && mainNav.classList.contains('open')) {
            if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                mainNav.classList.remove('open');
                menuToggle.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Otvori meni');
            }
        }
    });
    
    // Close dropdowns when window resizes to desktop
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            dropdownItems.forEach(function(item) {
                item.classList.remove('open');
            });
        }
    });
    
    // Alert dismiss
    document.querySelectorAll('.alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
    
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert.dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
});
