<?php
/**
 * API: Create category on the fly (admin only)
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data["name"] ?? "");
$parentId = !empty($data["parent_id"]) ? (int) $data["parent_id"] : null;

if (empty($name)) {
    echo json_encode(["error" => "Naziv kategorije je obavezan."]);
    exit;
}

$slug = slugify($name);

$existing = db()->fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
if ($existing) {
    $slug = $slug . "-" . time();
}

$id = db()->insert(
    "INSERT INTO categories (name, slug, parent_id, sort_order, active) VALUES (?, ?, ?, ?, 1)",
    [$name, $slug, $parentId, 0]
);

echo json_encode([
    "success" => true,
    "id" => $id,
    "name" => $name,
    "slug" => $slug,
    "parent_id" => $parentId
]);
