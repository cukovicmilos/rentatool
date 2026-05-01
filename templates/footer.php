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
            <h4>Kontakt</h4>
            <ul class="footer-contact">
                <li>✈️ Telegram: <a href="https://t.me/cukovicmilos" target="_blank" rel="noopener">@cukovicmilos</a></li>
                <li>📞 <a href="tel:0642039933">064 203 99 33 (od 15 do 20h)</a></li>
                <li>📍 <?= SITE_ADDRESS ?></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Dostava</h4>
            <ul class="footer-delivery">
                <li>Lično preuzimanje: <strong>0 <?= CURRENCY_SIGN ?></strong></li>
                <li>Dostava alata: <strong><?= DELIVERY_ONEWAY ?> <?= CURRENCY_SIGN ?></strong></li>
                <li>Dostava + povrat: <strong><?= DELIVERY_ROUNDTRIP ?> <?= CURRENCY_SIGN ?></strong></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4>Radno vreme</h4>
            <ul class="footer-hours">
                <li>Pon - Pet: 16:00 - 20:00</li>
                <li>Sub: 08:00 - 20:00</li>
                <li>Ned: Zatvoreno</li>
            </ul>
        </div>

    </div>
    
    <div class="footer-bottom">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Neka prava zadržana.</p>
        </div>
    </div>
</footer>

<script defer src="https://cloud.umami.is/script.js" data-website-id="26fd5173-e0a5-42a5-9434-87a024dd1c38"></script>
