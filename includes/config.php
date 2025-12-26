<?php
/**
 * Rent a Tool - Configuration
 */

// Load environment variables from .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;   // Skip invalid lines
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!empty($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('CLASSES_PATH', ROOT_PATH . '/classes');

// Base URL - adjust for your environment
define('BASE_URL', '/rentatool');

// Site settings
define('SITE_NAME', 'Rent a Tool');
define('SITE_DESCRIPTION', 'Iznajmljivanje građevinske opreme u Subotici i okolini');
define('SITE_EMAIL', 'info@rentatool.rs');
define('SITE_PHONE', '+381 24 123 456');
define('SITE_ADDRESS', 'Subotica, Srbija');

// Currency
if (!defined('CURRENCY')) define('CURRENCY', 'EUR');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '&euro;');

// Pricing rules
define('WEEKEND_MARKUP', 0.10);      // +10% for weekends
define('WEEKLY_DISCOUNT', 0.10);     // -10% for 7+ days
define('MIN_RENTAL_DAYS', 1);
define('MAX_RENTAL_DAYS', 10);
define('MAX_ADVANCE_DAYS', 30);      // Max days in advance for booking
define('MIN_CANCEL_DAYS', 2);        // Min days before start to cancel

// Delivery options (EUR)
define('DELIVERY_PICKUP', 0);        // Personal pickup
define('DELIVERY_ONEWAY', 10);       // Delivery only
define('DELIVERY_ROUNDTRIP', 15);    // Delivery + pickup

// Admin credentials (from .env or defaults)
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');
define('ADMIN_PASSWORD_HASH', password_hash(getenv('ADMIN_PASSWORD') ?: 'admin123', PASSWORD_DEFAULT));

// Database
define('DB_PATH', ROOT_PATH . '/database/rentatool.db');

// Upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);  // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('IMAGE_MAX_WIDTH', 1200);
define('IMAGE_MAX_HEIGHT', 1200);
define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);

// Telegram (from .env)
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

// Timezone
date_default_timezone_set('Europe/Belgrade');

// Autoload classes
spl_autoload_register(function ($class) {
    $file = CLASSES_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
