<?php
/**
 * Database Installation Script
 * Run this once to create the database and seed initial data
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "=== Rent a Tool - Database Installation ===\n\n";

try {
    $pdo = db()->getConnection();
    
    // Read and execute schema
    echo "Creating tables...\n";
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    echo "✓ Tables created successfully\n\n";
    
    // Seed admin user
    echo "Creating admin user...\n";
    $adminExists = db()->fetchColumn("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    if (!$adminExists) {
        db()->insert(
            "INSERT INTO admins (username, password_hash) VALUES (?, ?)",
            ['admin', password_hash('admin123', PASSWORD_DEFAULT)]
        );
        echo "✓ Admin user created (username: admin, password: admin123)\n";
    } else {
        echo "- Admin user already exists\n";
    }
    echo "\n";
    
    // Seed categories
    echo "Creating initial categories...\n";
    $categories = [
        ['Bušilice', 'busilice', 1],
        ['Brusilice', 'brusilice', 2],
        ['Slicerice', 'slicerice', 3],
        ['Ručni alati', 'rucni-alati', 4],
        ['Cirkulari', 'cirkulari', 5],
        ['Makaze', 'makaze', 6],
        ['Šmirgla/Poliranje', 'smirgla-poliranje', 7],
    ];
    
    foreach ($categories as $cat) {
        $exists = db()->fetchColumn("SELECT COUNT(*) FROM categories WHERE slug = ?", [$cat[1]]);
        if (!$exists) {
            db()->insert(
                "INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)",
                $cat
            );
            echo "  ✓ Added category: {$cat[0]}\n";
        } else {
            echo "  - Category exists: {$cat[0]}\n";
        }
    }
    echo "\n";
    
    // Seed settings
    echo "Creating default settings...\n";
    $settings = [
        ['site_name', 'Rent a Tool'],
        ['site_email', 'info@rentatool.rs'],
        ['site_phone', '+381 24 123 456'],
        ['site_address', 'Subotica, Srbija'],
        ['weekend_markup', '10'],
        ['weekly_discount', '10'],
        ['delivery_pickup', '0'],
        ['delivery_oneway', '10'],
        ['delivery_roundtrip', '15'],
        ['max_rental_days', '10'],
        ['max_advance_days', '30'],
        ['min_cancel_days', '2'],
    ];
    
    foreach ($settings as $setting) {
        $exists = db()->fetchColumn("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$setting[0]]);
        if (!$exists) {
            db()->insert(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)",
                $setting
            );
            echo "  ✓ Added setting: {$setting[0]}\n";
        } else {
            echo "  - Setting exists: {$setting[0]}\n";
        }
    }
    echo "\n";
    
    // Seed default pages
    echo "Creating default pages...\n";
    $pages = [
        ['o-nama', 'O nama', '<h2>O nama</h2><p>Rent a Tool je servis za iznajmljivanje građevinske opreme u Subotici i okolini.</p>', 1],
        ['kontakt', 'Kontakt', '<h2>Kontakt</h2><p>Telefon: +381 24 123 456<br>Email: info@rentatool.rs</p>', 2],
        ['uslovi-koriscenja', 'Uslovi korišćenja', '<h2>Uslovi korišćenja</h2><p>Ovde će biti uslovi korišćenja...</p>', 3],
    ];
    
    foreach ($pages as $page) {
        $exists = db()->fetchColumn("SELECT COUNT(*) FROM pages WHERE slug = ?", [$page[0]]);
        if (!$exists) {
            db()->insert(
                "INSERT INTO pages (slug, title, content, sort_order) VALUES (?, ?, ?, ?)",
                $page
            );
            echo "  ✓ Added page: {$page[1]}\n";
        } else {
            echo "  - Page exists: {$page[1]}\n";
        }
    }
    echo "\n";
    
    echo "=== Installation Complete ===\n";
    echo "\nIMPORTANT: Delete this file after installation!\n";
    echo "Change admin password in production!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
