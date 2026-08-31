<?php
/**
 * Migration: Add bundle product support
 * Date: 2026-08-29
 */

$pdo = db()->getConnection();

// Helper: check if a column exists
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("PRAGMA table_info({$table})");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

// Helper: check if a table exists
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

// 1. Add type column to tools
if (!columnExists($pdo, 'tools', 'type')) {
    $pdo->exec("ALTER TABLE tools ADD COLUMN type TEXT DEFAULT 'tool' CHECK(type IN ('tool', 'bundle'))");
}

// 2. Create bundle_items table
if (!tableExists($pdo, 'bundle_items')) {
    $pdo->exec("CREATE TABLE bundle_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bundle_id INTEGER NOT NULL,
        component_id INTEGER NOT NULL,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bundle_id) REFERENCES tools(id) ON DELETE CASCADE,
        FOREIGN KEY (component_id) REFERENCES tools(id) ON DELETE RESTRICT,
        UNIQUE(bundle_id, component_id)
    )");
    $pdo->exec("CREATE INDEX idx_bundle_items_bundle ON bundle_items(bundle_id)");
    $pdo->exec("CREATE INDEX idx_bundle_items_component ON bundle_items(component_id)");
}

// 3. Add is_bundle_component flag to reservation_items
if (!columnExists($pdo, 'reservation_items', 'is_bundle_component')) {
    $pdo->exec("ALTER TABLE reservation_items ADD COLUMN is_bundle_component INTEGER DEFAULT 0");
    $pdo->exec("CREATE INDEX idx_reservation_items_bundle_component ON reservation_items(is_bundle_component)");
}
