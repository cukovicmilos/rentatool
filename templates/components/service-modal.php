<!-- SERVICE ORDER MODAL -->
<div class="modal-overlay" id="serviceModal">
    <div class="modal-content">
        <button class="modal-close" id="closeServiceModal" type="button">&times;</button>
        <h2>Naruči uslugu</h2>
        
        <form id="serviceOrderForm">
            <div class="form-group">
                <label for="service_type" class="form-label required">Vrsta posla</label>
                <select id="service_type" name="service_type" class="form-control" required>
                    <option value="">-- Izaberi --</option>
                    <?php foreach ($serviceTypeLabels as $value => $label): ?>
                    <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="service_description" class="form-label required">Opis posla</label>
                <textarea id="service_description" name="service_description" class="form-control" rows="4" 
                          placeholder="Opiši šta treba uraditi..." required></textarea>
            </div>
            
            <div class="form-group">
                <label for="service_date" class="form-label required">Željeni datum</label>
                <div class="date-time-row">
                    <input type="date" id="service_date" name="service_date" class="form-control" required min="<?= $minDate ?>">
                    <input type="time" id="service_time" name="service_time" class="form-control" value="08:00">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Lokacija</label>
                <div class="service-location-options">
                    <label class="radio-option">
                        <input type="radio" name="service_location" value="workshop" required>
                        <span>Doneti u radionicu</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="service_location" value="onsite">
                        <span>Doći kod vas</span>
                    </label>
                </div>
            </div>
            
            <div id="serviceFormErrors" class="alert alert-error" style="display:none"></div>
            
            <button type="submit" class="btn btn-primary btn-block">Dodaj u korpu</button>
        </form>
    </div>
</div>

<style>
.date-time-row {
    display: flex;
    gap: 8px;
}
.date-time-row input {
    flex: 1;
    min-width: 0;
}

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: var(--color-white);
    padding: var(--spacing-xl);
    border-radius: var(--border-radius);
    max-width: 500px;
    width: 90%;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-close {
    position: absolute;
    top: var(--spacing-sm);
    right: var(--spacing-sm);
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    padding: var(--spacing-xs);
}

.service-location-options {
    display: flex;
    gap: var(--spacing-md);
}

.radio-option {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    flex: 1;
}

.radio-option:has(input:checked) {
    border-color: var(--color-accent);
    background: var(--color-accent-light);
}

@media (max-width: 768px) {
    .service-location-options {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const serviceModal = document.getElementById('serviceModal');
    const openBtn = document.getElementById('openServiceModal');
    const closeBtn = document.getElementById('closeServiceModal');
    
    if (openBtn && serviceModal) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            serviceModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        closeBtn.addEventListener('click', function() {
            serviceModal.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        serviceModal.addEventListener('click', function(e) {
            if (e.target === serviceModal) {
                serviceModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        document.getElementById('serviceOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const errorsDiv = document.getElementById('serviceFormErrors');
            const formData = new FormData(form);
            
            const serviceTime = document.getElementById('service_time').value || '08:00';
            fetch('<?= url('api/cart') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add_service',
                    service_type: formData.get('service_type'),
                    description: formData.get('service_description'),
                    service_date: formData.get('service_date'),
                    service_time: serviceTime,
                    location: formData.get('service_location')
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= url('korpa') ?>';
                } else {
                    errorsDiv.textContent = data.error || 'Došlo je do greške. Pokušajte ponovo.';
                    errorsDiv.style.display = 'block';
                }
            })
            .catch(err => {
                errorsDiv.textContent = 'Došlo je do greške. Pokušajte ponovo.';
                errorsDiv.style.display = 'block';
            });
        });
    }
});
</script>
