<?php
/**
 * Homepage - Tool Listing
 */

// Get all available tools with primary image
$tools = db()->fetchAll("
    SELECT t.*,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t 
    WHERE t.status IN ('available', 'rented')
    ORDER BY t.featured DESC, t.created_at DESC
");

// Page settings
$pageTitle = SITE_NAME . ' - Iznajmljivanje građevinske opreme';
$pageDescription = SITE_DESCRIPTION;
$bodyClass = 'home-page';

ob_start();
?>

<div class="page-header">
    <h1>Iznajmljivanje građevinske opreme</h1>
    <p class="text-muted">Kvalitetna oprema za vaše projekte u Subotici i okolini</p>
</div>

<?php if (empty($tools)): ?>
    <div class="alert alert-info">
        Trenutno nema dostupnih alata. Molimo posetite nas ponovo.
    </div>
<?php else: ?>
    <div class="tools-grid">
        <?php foreach ($tools as $tool): ?>
            <?php include TEMPLATES_PATH . '/components/tool-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="info-section mt-4">
    <h2>Kako funkcioniše?</h2>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-icon">1</span>
            <h3>Odaberite alat</h3>
            <p>Pregledajte naš katalog i izaberite alate koji vam trebaju.</p>
        </div>
        <div class="info-item">
            <span class="info-icon">2</span>
            <h3>Izaberite datume</h3>
            <p>Odaberite period iznajmljivanja (do 10 dana).</p>
        </div>
        <div class="info-item">
            <span class="info-icon">3</span>
            <h3>Potvrdite rezervaciju</h3>
            <p>Unesite svoje podatke i potvrdite rezervaciju.</p>
        </div>
        <div class="info-item">
            <span class="info-icon">4</span>
            <h3>Preuzmite alat</h3>
            <p>Preuzmite lično ili izaberite dostavu.</p>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: var(--spacing-xl);
}

.info-section {
    background: var(--color-gray-100);
    padding: var(--spacing-xl);
    border-radius: var(--border-radius);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-top: var(--spacing-lg);
}

.info-item {
    text-align: center;
}

.info-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--color-accent);
    color: var(--color-black);
    font-weight: 700;
    border-radius: 50%;
    margin-bottom: var(--spacing-md);
}

.info-item h3 {
    font-size: var(--font-size-base);
    margin-bottom: var(--spacing-sm);
}

.info-item p {
    font-size: var(--font-size-small);
    color: var(--color-gray-500);
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
