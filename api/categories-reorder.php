<?php
/**
 * API: Reorder categories via drag and drop (admin only)
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
$order = $data["order"] ?? [];

if (empty($order) || !is_array($order)) {
    echo json_encode(["error" => "Nema podataka za čuvanje."]);
    exit;
}

db()->beginTransaction();
try {
    foreach ($order as $i => $id) {
        db()->execute(
            "UPDATE categories SET sort_order = ? WHERE id = ?",
            [$i, (int)$id]
        );
    }
    db()->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    db()->rollback();
    echo json_encode(["error" => "Greška: " . $e->getMessage()]);
}
