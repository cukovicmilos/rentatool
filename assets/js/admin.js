/**
 * Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Da li ste sigurni?')) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic specifications
    const addSpecBtn = document.getElementById('addSpec');
    const specList = document.getElementById('specList');
    
    if (addSpecBtn && specList) {
        addSpecBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'spec-row';
            row.innerHTML = `
                <input type="text" name="spec_names[]" class="form-control" placeholder="Naziv (npr. Snaga)">
                <input type="text" name="spec_values[]" class="form-control" placeholder="Vrednost (npr. 1500W)">
                <button type="button" class="btn btn-danger btn-small remove-spec">&times;</button>
            `;
            specList.appendChild(row);
        });
        
        specList.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-spec')) {
                e.target.closest('.spec-row').remove();
            }
        });
    }
    
    // Image preview
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            
            Array.from(this.files).forEach(function(file) {
                if (!file.type.startsWith('image/')) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'image-preview';
                    div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }
    
    // Mark current nav link as active
    const currentPath = window.location.pathname;
    document.querySelectorAll('.admin-nav-link').forEach(function(link) {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
    
});
