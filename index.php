<?php
/**
 * Rent a Tool - Front Controller / Router
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Get request URI and remove base path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strlen($basePath));
$path = trim($path, '/');

// Parse path segments
$segments = $path ? explode('/', $path) : [];

// Router
$route = $segments[0] ?? '';

switch ($route) {
    // Admin routes
    case 'admin':
        $adminPage = $segments[1] ?? 'index';
        $action = $segments[2] ?? null;
        $id = $segments[3] ?? null;
        
        // Set GET params for actions
        if ($action) $_GET['action'] = $action;
        if ($id) $_GET['id'] = $id;
        
        $adminFile = __DIR__ . '/admin/' . $adminPage . '.php';
        if (file_exists($adminFile)) {
            require_once $adminFile;
        } else {
            http_response_code(404);
            echo "404 - Admin stranica nije pronađena";
        }
        break;
    
    // Category page
    case 'kategorija':
        $_GET['slug'] = $segments[1] ?? '';
        require_once __DIR__ . '/pages/kategorija.php';
        break;
    
    // Tool detail page
    case 'alat':
        $_GET['slug'] = $segments[1] ?? '';
        require_once __DIR__ . '/pages/alat.php';
        break;
    
    // Static page
    case 'stranica':
        $_GET['slug'] = $segments[1] ?? '';
        require_once __DIR__ . '/pages/stranica.php';
        break;
    
    // Cart
    case 'korpa':
        require_once __DIR__ . '/pages/korpa.php';
        break;
    
    // Checkout
    case 'checkout':
        require_once __DIR__ . '/pages/checkout.php';
        break;
    
    // Thank you page
    case 'zahvalnica':
        require_once __DIR__ . '/pages/zahvalnica.php';
        break;
    
    // Reservation view/cancel
    case 'rezervacija':
        if (isset($segments[2]) && $segments[1] === 'otkazi') {
            $_GET['code'] = $segments[2];
            require_once __DIR__ . '/pages/otkazi.php';
        } else {
            $_GET['code'] = $segments[1] ?? '';
            require_once __DIR__ . '/pages/rezervacija.php';
        }
        break;
    
    // API routes
    case 'api':
        $apiEndpoint = $segments[1] ?? '';
        $apiFile = __DIR__ . '/api/' . $apiEndpoint . '.php';
        if (file_exists($apiFile)) {
            require_once $apiFile;
        } else {
            http_response_code(404);
            jsonResponse(['error' => 'API endpoint not found'], 404);
        }
        break;
    
    // Tools listing page
    case 'alati':
        require_once __DIR__ . '/pages/alati.php';
        break;
    
    // Homepage (empty path) - now promo landing
    case '':
        require_once __DIR__ . '/pages/promo.php';
        break;
    
    // Try pages folder
    default:
        $pageFile = __DIR__ . '/pages/' . $route . '.php';
        if (file_exists($pageFile)) {
            require_once $pageFile;
        } else {
            http_response_code(404);
            echo "404 - Stranica nije pronađena";
        }
        break;
}
