<?php
/**
 * Footer Template
 */
?>
<footer class="site-footer">
    <div class="footer-container">
        
        <div class="footer-section">
            <h4>O servisu</h4>
            <p><?= SITE_DESCRIPTION ?></p>
        </div>
        
        <div class="footer-section">
            <h4>Brzi linkovi</h4>
            <ul class="footer-links">
                <li><a href="<?= url('') ?>">Početna</a></li>
                <li><a href="<?= url('alati') ?>">Alati</a></li>
                <li><a href="<?= url('stranica/o-nama') ?>">O nama</a></li>
                <li><a href="<?= url('stranica/kontakt') ?>">Kontakt</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Kontakt</h4>
            <ul class="footer-contact">
                <li>📞 <?= SITE_PHONE ?></li>
                <li>✉️ <?= SITE_EMAIL ?></li>
                <li>📍 <?= SITE_ADDRESS ?></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Dostava</h4>
            <ul class="footer-delivery">
                <li>Lično preuzimanje: <strong>0 <?= CURRENCY_SIGN ?></strong></li>
                <li>Samo dostava: <strong><?= DELIVERY_ONEWAY ?> <?= CURRENCY_SIGN ?></strong></li>
                <li>Dostava + povrat: <strong><?= DELIVERY_ROUNDTRIP ?> <?= CURRENCY_SIGN ?></strong></li>
            </ul>
        </div>
        
    </div>
    
    <div class="footer-bottom">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Neka prava zadržana.</p>
        </div>
    </div>
</footer>
