<?php
/**
 * Tool Card Component
 * 
 * Expects $tool array with: id, name, slug, price_24h, status, featured, primary_image (optional)
 */

if (empty($tool)) return;

// Use primary_image if already fetched, otherwise query
if (!isset($tool['primary_image'])) {
    $primaryImage = db()->fetch("
        SELECT filename FROM tool_images 
        WHERE tool_id = ? AND is_primary = 1 
        LIMIT 1
    ", [$tool['id']]);
    $tool['primary_image'] = $primaryImage['filename'] ?? null;
}

$imageUrl = $tool['primary_image'] 
    ? upload('tools/' . $tool['primary_image'])
    : asset('images/no-image.png');

$weekendPrice = $tool['price_24h'] * (1 + WEEKEND_MARKUP);
?>
<article class="tool-card">
    <?php if ($tool['status'] === 'rented'): ?>
    <span class="tool-badge badge-rented">Iznajmljen</span>
    <?php elseif ($tool['status'] === 'maintenance'): ?>
    <span class="tool-badge badge-maintenance">Servis</span>
    <?php elseif (!empty($tool['featured'])): ?>
    <span class="tool-badge badge-featured">Preporučeno</span>
    <?php endif; ?>
    <a href="<?= url('alat/' . $tool['slug']) ?>" class="tool-card-link">
        <div class="tool-card-image">
            <picture>
                <?php if ($tool['primary_image']):
                    $cardWebp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $tool['primary_image']);
                    if (file_exists(UPLOADS_PATH . '/tools/' . $cardWebp)): ?>
                <source srcset="<?= upload('tools/' . $cardWebp) ?>" type="image/webp">
                <?php endif; endif; ?>
                <img src="<?= e($imageUrl) ?>"
                     alt="<?= e($tool['name']) ?>"
                     width="400"
                     height="300"
                     loading="lazy">
            </picture>
        </div>
        
        <div class="tool-card-content">
            <h3 class="tool-card-title"><?= e($tool['name']) ?></h3>
            
            <?php if (!empty($tool['short_description'])): ?>
            <p class="tool-card-description"><?= e(truncate($tool['short_description'], 80)) ?></p>
            <?php endif; ?>
            
            <div class="tool-card-price">
                <span class="price-amount"><?= formatPrice($tool['price_24h']) ?></span>
                <span class="price-period">/ 24h</span>
            </div>
            <div class="tool-card-weekend">
                <small class="text-muted">Vikend: <?= formatPrice($weekendPrice) ?></small>
            </div>
            
            <?php if ($tool['status'] === 'available'): ?>
            <span class="btn btn-primary btn-small">Pogledaj</span>
            <?php else: ?>
            <span class="btn btn-disabled btn-small">Nedostupan</span>
            <?php endif; ?>
        </div>
    </a>
</article>
