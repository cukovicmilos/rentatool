<?php

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
$order = $data["order"] ?? [];
$toolId = (int) ($data["tool_id"] ?? 0);
$csrfToken = $data["csrf_token"] ?? "";

if (empty($csrfToken) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    http_response_code(403);
    echo json_encode(["error" => "Nevažeći CSRF token."]);
    exit;
}

if (empty($order) || !is_array($order) || !$toolId) {
    echo json_encode(["error" => "Nema podataka za čuvanje."]);
    exit;
}

db()->beginTransaction();
try {
    foreach ($order as $i => $imageId) {
        db()->execute(
            "UPDATE tool_images SET sort_order = ? WHERE id = ? AND tool_id = ?",
            [$i, (int)$imageId, $toolId]
        );
    }

    db()->execute("UPDATE tool_images SET is_primary = 0 WHERE tool_id = ?", [$toolId]);
    if (!empty($order)) {
        db()->execute("UPDATE tool_images SET is_primary = 1 WHERE id = ? AND tool_id = ?", [(int)$order[0], $toolId]);
    }

    db()->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    db()->rollback();
    echo json_encode(["error" => "Greška: " . $e->getMessage()]);
}
