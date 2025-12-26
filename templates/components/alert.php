<?php
/**
 * Alert Component
 * 
 * Usage:
 * $alertType = 'success'; // success, error, warning, info
 * $alertMessage = 'Operation completed successfully!';
 * include TEMPLATES_PATH . '/components/alert.php';
 */

$alertType = $alertType ?? 'info';
$alertMessage = $alertMessage ?? '';
$alertDismissible = $alertDismissible ?? true;

if (empty($alertMessage)) return;
?>
<div class="alert alert-<?= e($alertType) ?> <?= $alertDismissible ? 'dismissible' : '' ?>" role="alert">
    <span class="alert-message"><?= e($alertMessage) ?></span>
    <?php if ($alertDismissible): ?>
    <button type="button" class="alert-close" aria-label="Zatvori">&times;</button>
    <?php endif; ?>
</div>
