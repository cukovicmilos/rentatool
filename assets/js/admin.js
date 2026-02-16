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
    
    // Dynamic video links
    const addVideoBtn = document.getElementById('addVideo');
    const videoList = document.getElementById('videoList');
    
    if (addVideoBtn && videoList) {
        addVideoBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'spec-row video-row';
            row.innerHTML = `
                <input type="text" name="video_titles[]" class="form-control" placeholder="Naslov (opciono)" style="flex: 1;">
                <input type="url" name="video_urls[]" class="form-control" placeholder="https://www.youtube.com/watch?v=..." style="flex: 2;">
                <button type="button" class="btn btn-danger btn-small remove-video">&times;</button>
            `;
            videoList.appendChild(row);
        });
        
        videoList.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-video')) {
                e.target.closest('.video-row').remove();
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

// Category on-the-fly creation
function toggleNewCategory() {
    const form = document.getElementById('newCategoryForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        document.getElementById('newCatName').focus();
    }
}

function createCategory() {
    const nameInput = document.getElementById('newCatName');
    const parentSelect = document.getElementById('newCatParent');
    const name = nameInput.value.trim();

    if (!name) {
        nameInput.focus();
        return;
    }

    const baseUrl = document.querySelector('meta[name="base-url"]')?.content
        || window.location.pathname.split('/admin/')[0];

    fetch(baseUrl + '/api/category', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: name,
            parent_id: parentSelect.value || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        const tree = document.getElementById('categoryTree');
        const parentId = data.parent_id;

        if (parentId) {
            // Find parent group and add as child
            const parentCheckbox = tree.querySelector('input[value="' + parentId + '"]');
            if (parentCheckbox) {
                const group = parentCheckbox.closest('.cat-group');
                let childrenDiv = group.querySelector('.cat-children');
                if (!childrenDiv) {
                    childrenDiv = document.createElement('div');
                    childrenDiv.className = 'cat-children';
                    group.appendChild(childrenDiv);
                }
                const label = document.createElement('label');
                label.className = 'cat-child';
                label.innerHTML = '<input type="checkbox" name="categories[]" value="' + data.id + '" checked> <span class="cat-name">' + data.name + '</span>';
                childrenDiv.appendChild(label);
            }
        } else {
            // Add as new parent group
            const group = document.createElement('div');
            group.className = 'cat-group';
            group.innerHTML = '<label class="cat-parent"><input type="checkbox" name="categories[]" value="' + data.id + '" checked> <span class="cat-name">' + data.name + '</span></label>';
            tree.appendChild(group);

            // Also add to parent select dropdown
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = '↳ Pod-kategorija od: ' + data.name;
            parentSelect.appendChild(opt);
        }

        // Reset form
        nameInput.value = '';
        parentSelect.value = '';
        document.getElementById('newCategoryForm').style.display = 'none';
    })
    .catch(err => alert('Greška: ' + err.message));
}
