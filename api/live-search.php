<?php
/**
 * API: Live search tools for frontend dropdown (public)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$normalized = normalizeSerbian($q);
$searchTerm = '%' . $normalized . '%';

$nameNorm = sqlNormalizeSerbian('t.name');
$shortNorm = sqlNormalizeSerbian('t.short_description');

$tools = db()->fetchAll("
    SELECT t.id, t.name, t.slug, t.short_description,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t
    WHERE t.status IN ('available', 'rented')
      AND ({$nameNorm} LIKE ? OR {$shortNorm} LIKE ?)
    ORDER BY
        CASE WHEN {$nameNorm} LIKE ? THEN 0 ELSE 1 END,
        t.name
    LIMIT 8
", [$searchTerm, $searchTerm, $searchTerm]);

echo json_encode($tools);
