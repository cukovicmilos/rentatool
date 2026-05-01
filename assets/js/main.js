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
    
    // Live search dropdown
    function initLiveSearch(inputId, dropdownId) {
        var searchInput = document.getElementById(inputId);
        var searchDropdown = document.getElementById(dropdownId);
        
        if (!searchInput || !searchDropdown) return;
        
        var searchTimeout = null;
        var currentQuery = '';
        
        function closeDropdown() {
            searchDropdown.hidden = true;
            searchDropdown.innerHTML = '';
        }
        
        function renderResults(tools, query) {
            if (!tools.length) {
                searchDropdown.innerHTML = '<div class="search-dropdown-no-results">Nema rezultata</div>';
                searchDropdown.hidden = false;
                return;
            }
            
            var html = '';
            tools.forEach(function(tool) {
                var shortDesc = tool.short_description 
                    ? tool.short_description.substring(0, 60) + (tool.short_description.length > 60 ? '...' : '') 
                    : '';
                html += '<a href="/alat/' + encodeURIComponent(tool.slug) + '" class="search-dropdown-item">';
                if (tool.primary_image) {
                    html += '<img src="/uploads/tools/' + encodeURIComponent(tool.primary_image) + '" alt="' + escapeHtml(tool.name) + '" width="40" height="40" loading="lazy">';
                }
                html += '<div class="search-dropdown-item-info">';
                html += '<div class="search-dropdown-item-name">' + escapeHtml(tool.name) + '</div>';
                if (shortDesc) {
                    html += '<div class="search-dropdown-item-desc">' + escapeHtml(shortDesc) + '</div>';
                }
                html += '</div></a>';
            });
            
            html += '<a href="/pretraga?q=' + encodeURIComponent(query) + '" class="search-dropdown-view-all">Prikaži sve rezultate →</a>';
            searchDropdown.innerHTML = html;
            searchDropdown.hidden = false;
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            currentQuery = query;
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            if (query.length < 2) {
                closeDropdown();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                fetch('/api/live-search?q=' + encodeURIComponent(query))
                    .then(function(res) { return res.json(); })
                    .then(function(tools) {
                        if (currentQuery === query) {
                            renderResults(tools, query);
                        }
                    })
                    .catch(function() {
                        closeDropdown();
                    });
            }, 300);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
                this.blur();
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                closeDropdown();
            }
        });
    }
    
    initLiveSearch('searchInput', 'searchDropdown');
    initLiveSearch('mobileSearchInput', 'mobileSearchDropdown');
    
    // Mobile search toggle
    var mobileSearchToggle = document.getElementById('mobileSearchToggle');
    var mobileSearchBar = document.getElementById('mobileSearchBar');
    var mobileSearchInput = document.getElementById('mobileSearchInput');
    
    if (mobileSearchToggle && mobileSearchBar) {
        mobileSearchToggle.addEventListener('click', function() {
            var isOpen = mobileSearchBar.hidden;
            mobileSearchBar.hidden = !isOpen;
            mobileSearchToggle.setAttribute('aria-expanded', isOpen);
            if (isOpen && mobileSearchInput) {
                mobileSearchInput.focus();
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!mobileSearchBar.hidden && 
                !mobileSearchBar.contains(e.target) && 
                !mobileSearchToggle.contains(e.target)) {
                mobileSearchBar.hidden = true;
                mobileSearchToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
});
