<?php
/**
 * Database Migration Runner
 *
 * Applies migrations from database/migrations/ in filename order.
 * Supports both .sql files and .php files.
 * Tracks applied migrations in the `migrations` table.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$migrationsDir = __DIR__ . '/migrations';

// Ensure migrations table exists
db()->execute("CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT UNIQUE NOT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Get already applied migrations
$applied = db()->fetchAll("SELECT filename FROM migrations");
$appliedMap = array_column($applied, 'filename', 'filename');

// Find migration files
$files = glob($migrationsDir . '/*');
$files = array_filter($files, fn($f) => is_file($f) && (str_ends_with($f, '.sql') || str_ends_with($f, '.php')));
sort($files);

$ran = [];
foreach ($files as $file) {
    $filename = basename($file);
    if (isset($appliedMap[$filename])) {
        continue;
    }

    echo "Applying {$filename}...\n";

    db()->beginTransaction();
    try {
        if (str_ends_with($file, '.php')) {
            require $file;
        } else {
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new Exception("Could not read {$filename}");
            }
            db()->getConnection()->exec($sql);
        }

        db()->insert("INSERT INTO migrations (filename) VALUES (?)", [$filename]);
        db()->commit();
        $ran[] = $filename;
    } catch (Exception $e) {
        db()->rollback();
        echo "ERROR applying {$filename}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (empty($ran)) {
    echo "No new migrations to apply.\n";
} else {
    echo "Applied " . count($ran) . " migration(s):\n";
    foreach ($ran as $filename) {
        echo "  - {$filename}\n";
    }
}
