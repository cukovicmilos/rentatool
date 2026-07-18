<?php
/**
 * One-off migration: rename tool images to SEO-friendly slug-based filenames.
 *
 * {tool-slug}.jpg        → primary image
 * {tool-slug}-2.jpg      → additional images
 * Renames .webp counterparts too and updates tool_images.filename.
 *
 * Usage:
 *   php scripts/migrate-image-filenames.php --dry-run   (preview)
 *   php scripts/migrate-image-filenames.php             (execute)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$dryRun = in_array('--dry-run', $argv ?? []);

$tools = db()->fetchAll("SELECT id, name, slug FROM tools ORDER BY id");
$renamed = 0;
$skipped = 0;
$errors = [];

// Track claimed filenames to avoid collisions
$claimed = [];
$rows = db()->fetchAll("SELECT filename FROM tool_images");
foreach ($rows as $r) {
    $claimed[$r['filename']] = true;
}

foreach ($tools as $tool) {
    $slug = $tool['slug'] ?: slugify($tool['name']);
    if (!$slug) {
        $errors[] = "Tool #{$tool['id']} ({$tool['name']}): no slug, skipped";
        continue;
    }

    $images = db()->fetchAll(
        "SELECT * FROM tool_images WHERE tool_id = ? ORDER BY is_primary DESC, sort_order, id",
        [$tool['id']]
    );

    $i = 0;
    foreach ($images as $img) {
        $i++;
        $oldName = $img['filename'];
        $ext = strtolower(pathinfo($oldName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $errors[] = "Tool #{$tool['id']}: unexpected extension in {$oldName}, skipped";
            continue;
        }

        // Find a free target name
        $suffix = ($i === 1) ? '' : '-' . $i;
        $newName = $slug . $suffix . '.' . $ext;
        while ($newName !== $oldName && (isset($claimed[$newName]) || file_exists(UPLOADS_PATH . '/tools/' . $newName))) {
            $i++;
            $newName = $slug . '-' . $i . '.' . $ext;
        }

        if ($newName === $oldName) {
            $skipped++;
            continue;
        }

        $oldPath = UPLOADS_PATH . '/tools/' . $oldName;
        $newPath = UPLOADS_PATH . '/tools/' . $newName;
        // WebP counterpart exists only for jpg/png originals
        $oldWebp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $oldPath);
        $newWebp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $newPath);
        $hasWebpCounterpart = ($oldWebp !== $oldPath) && file_exists($oldWebp);

        if (!file_exists($oldPath)) {
            $errors[] = "Tool #{$tool['id']} ({$slug}): file missing {$oldName}, DB row skipped";
            continue;
        }

        echo ($dryRun ? '[DRY] ' : '') . "{$oldName} → {$newName}\n";

        if (!$dryRun) {
            if (!rename($oldPath, $newPath)) {
                $errors[] = "Failed to rename {$oldName}";
                continue;
            }
            if ($hasWebpCounterpart) {
                rename($oldWebp, $newWebp);
            }
            db()->execute("UPDATE tool_images SET filename = ? WHERE id = ?", [$newName, $img['id']]);
        }

        unset($claimed[$oldName]);
        $claimed[$newName] = true;
        $renamed++;
    }
}

echo "\n" . ($dryRun ? '[DRY RUN] ' : '') . "Renamed: {$renamed}, already OK: {$skipped}, errors: " . count($errors) . "\n";
foreach ($errors as $e) {
    echo "  ERROR: {$e}\n";
}
