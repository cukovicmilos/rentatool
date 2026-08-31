<?php
/**
 * API: Search tools by name (admin only, for autocomplete)
 */

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/functions.php";

header("Content-Type: application/json");

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(["error" => "Nemate pristup."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit;
}

$q = trim($_GET["q"] ?? "");
$exclude = (int) ($_GET["exclude"] ?? 0);
$excludeBundles = isset($_GET["exclude_bundles"]);

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$params = ["%" . $q . "%"];
$excludeClause = "";
if ($exclude > 0) {
    $excludeClause .= " AND t.id != ?";
    $params[] = $exclude;
}
if ($excludeBundles) {
    $excludeClause .= " AND (t.type IS NULL OR t.type != 'bundle')";
}

$tools = db()->fetchAll("
    SELECT t.id, t.name, t.type,
           (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM tools t
    WHERE t.name LIKE ? {$excludeClause}
    ORDER BY t.name
    LIMIT 10
", $params);

echo json_encode($tools);
