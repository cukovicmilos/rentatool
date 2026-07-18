<?php
/**
 * Guide Pages - "Kako se radi" vodiči iz tool_jobs
 *
 * /vodic            → index svih vodiča
 * /vodic/{alat}/{posao} → pojedinačni vodič
 */

$toolSlug = get('tool', '');
$jobSlug = get('job', '');

// ============================================
// INDEX: lista svih vodiča grupisana po alatu
// ============================================
if ($toolSlug === '') {
    $toolsWithJobs = db()->fetchAll("
        SELECT t.id, t.name, t.slug,
               (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM tools t
        WHERE t.status != 'inactive'
          AND EXISTS (SELECT 1 FROM tool_jobs tj WHERE tj.tool_id = t.id)
        ORDER BY t.name
    ");

    $jobsByTool = [];
    foreach ($toolsWithJobs as $t) {
        $jobsByTool[$t['id']] = db()->fetchAll("
            SELECT * FROM tool_jobs WHERE tool_id = ? ORDER BY sort_order
        ", [$t['id']]);
    }

    $pageTitle = 'Vodiči i saveti za rad sa alatom | ' . SITE_NAME;
    $pageDescription = 'Praktični vodiči i saveti: koji poslovi se mogu uraditi sa kojim alatom i kako. Iznajmljivanje alata u Subotici.';
    $breadcrumbs = [
        ['title' => 'Početna', 'url' => url('')],
        ['title' => 'Vodiči']
    ];

    ob_start();
    ?>

    <div class="page-header">
        <h1>Vodiči i saveti za rad sa alatom</h1>
        <p class="text-muted">Praktična uputstva koja vam pomažu da posao uradite sami — uz pravi alat.</p>
    </div>

    <?php foreach ($toolsWithJobs as $t): ?>
    <section class="guide-tool-section mb-3">
        <h2>
            <a href="<?= url('alat/' . $t['slug']) ?>"><?= e($t['name']) ?></a>
        </h2>
        <ul class="guide-list">
            <?php foreach ($jobsByTool[$t['id']] as $job): ?>
            <li>
                <a href="<?= url('vodic/' . $t['slug'] . '/' . slugify($job['title'])) ?>">
                    <?= e($job['title']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>

    <style>
    .guide-tool-section h2 {
        font-size: var(--font-size-large);
        margin-bottom: var(--spacing-sm);
        padding-bottom: var(--spacing-sm);
        border-bottom: 2px solid var(--color-accent);
    }
    .guide-list {
        padding-left: var(--spacing-lg);
    }
    .guide-list li {
        list-style: disc;
        margin-bottom: var(--spacing-xs);
    }
    .guide-list a {
        color: var(--color-gray-600);
        text-decoration: underline;
    }
    .guide-list a:hover {
        color: var(--color-accent);
    }
    </style>

    <?php
    $content = ob_get_clean();
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// ============================================
// DETAIL: pojedinačni vodič
// ============================================

$tool = db()->fetch("SELECT * FROM tools WHERE slug = ? AND status != 'inactive'", [$toolSlug]);

if (!$tool) {
    http_response_code(404);
    $pageTitle = 'Vodič nije pronađen';
    $content = '<div class="alert alert-error">Vodič nije pronađen.</div><p><a href="' . url('vodic') . '">← Svi vodiči</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

// Find job by slugified title
$jobs = db()->fetchAll("SELECT * FROM tool_jobs WHERE tool_id = ? ORDER BY sort_order", [$tool['id']]);
$job = null;
foreach ($jobs as $j) {
    if (slugify($j['title']) === $jobSlug) {
        $job = $j;
        break;
    }
}

if (!$job) {
    http_response_code(404);
    $pageTitle = 'Vodič nije pronađen';
    $content = '<div class="alert alert-error">Vodič nije pronađen.</div><p><a href="' . url('vodic') . '">← Svi vodiči</a></p>';
    include TEMPLATES_PATH . '/layout.php';
    exit;
}

$primaryImage = db()->fetch("SELECT filename FROM tool_images WHERE tool_id = ? AND is_primary = 1 LIMIT 1", [$tool['id']]);

$pageTitle = $job['title'] . ' - ' . $tool['name'] . ' | ' . SITE_NAME;
$pageDescription = truncate($job['title'] . ' uz alat ' . $tool['name'] . '. ' . $job['description'], 155);
$pageImage = $primaryImage ? '/uploads/tools/' . $primaryImage['filename'] : null;
$canonicalUrl = '/vodic/' . $toolSlug . '/' . $jobSlug;

// Article schema
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $job['title'],
    'description' => truncate($job['description'], 200),
    'author' => [
        '@type' => 'Organization',
        'name' => SITE_NAME,
        'url' => 'https://rentatool.in.rs' . BASE_URL
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => SITE_NAME,
        'url' => 'https://rentatool.in.rs' . BASE_URL
    ],
    'datePublished' => !empty($job['created_at']) ? date('Y-m-d', strtotime($job['created_at'])) : date('Y-m-d'),
    'mainEntityOfPage' => 'https://rentatool.in.rs' . BASE_URL . '/vodic/' . $toolSlug . '/' . $jobSlug
];
if ($primaryImage) {
    $schemaData['image'] = 'https://rentatool.in.rs' . BASE_URL . '/uploads/tools/' . $primaryImage['filename'];
}

$breadcrumbs = [
    ['title' => 'Početna', 'url' => url('')],
    ['title' => 'Vodiči', 'url' => url('vodic')],
    ['title' => $tool['name'], 'url' => url('alat/' . $tool['slug'])],
    ['title' => $job['title']]
];

ob_start();
?>

<article class="guide-page">
    <h1><?= e($job['title']) ?></h1>

    <p class="guide-tool-link">
        Alat: <a href="<?= url('alat/' . $tool['slug']) ?>"><?= e($tool['name']) ?></a>
        — <?= formatPrice($tool['price_24h']) ?>/dan
    </p>

    <div class="guide-content">
        <?= nl2br(e($job['description'])) ?>
    </div>

    <div class="guide-cta">
        <p><strong>Treba vam alat za ovaj posao?</strong></p>
        <a href="<?= url('alat/' . $tool['slug']) ?>" class="btn btn-primary btn-large">Iznajmi <?= e($tool['name']) ?> →</a>
    </div>

    <?php
    $otherJobs = array_filter($jobs, fn($j) => $j['id'] !== $job['id']);
    if (!empty($otherJobs)):
    ?>
    <div class="guide-related">
        <h2>Ostali poslovi sa alatom <?= e($tool['name']) ?></h2>
        <ul>
            <?php foreach ($otherJobs as $other): ?>
            <li><a href="<?= url('vodic/' . $tool['slug'] . '/' . slugify($other['title'])) ?>"><?= e($other['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</article>

<style>
.guide-page {
    max-width: 800px;
}
.guide-tool-link {
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-lg);
}
.guide-tool-link a {
    text-decoration: underline;
}
.guide-content {
    line-height: 1.8;
    margin-bottom: var(--spacing-xl);
}
.guide-cta {
    background: var(--color-gray-100);
    border-left: 4px solid var(--color-accent);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}
.guide-cta p {
    margin-top: 0;
}
.guide-related ul {
    padding-left: var(--spacing-lg);
}
.guide-related li {
    list-style: disc;
    margin-bottom: var(--spacing-xs);
}
.guide-related a {
    color: var(--color-gray-600);
    text-decoration: underline;
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
