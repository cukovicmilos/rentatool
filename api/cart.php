<?php
/**
 * Cart API Endpoint
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? get('action', '');

switch ($action) {
    case 'add':
        $toolId = (int) ($input['tool_id'] ?? 0);
        $dateStart = $input['date_start'] ?? '';
        $dateEnd = $input['date_end'] ?? '';
        
        // Validate
        if (!$toolId || !$dateStart || !$dateEnd) {
            jsonResponse(['success' => false, 'error' => 'Nedostaju podaci.'], 400);
        }
        
        // Check tool exists and is available
        $tool = db()->fetch("SELECT * FROM tools WHERE id = ? AND status = 'available'", [$toolId]);
        if (!$tool) {
            jsonResponse(['success' => false, 'error' => 'Alat nije dostupan.'], 400);
        }
        
        // Validate dates
        $start = strtotime($dateStart);
        $end = strtotime($dateEnd);
        $today = strtotime('today');
        $maxDate = strtotime('+' . MAX_ADVANCE_DAYS . ' days');
        
        if ($start < $today || $end < $today) {
            jsonResponse(['success' => false, 'error' => 'Datumi moraju biti u budućnosti.'], 400);
        }
        
        if ($end < $start) {
            jsonResponse(['success' => false, 'error' => 'Datum završetka mora biti posle početka.'], 400);
        }
        
        $days = ceil(($end - $start) / 86400) + 1;
        if ($days > MAX_RENTAL_DAYS) {
            jsonResponse(['success' => false, 'error' => 'Maksimalno ' . MAX_RENTAL_DAYS . ' dana.'], 400);
        }
        
        // Check if tool already in cart
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['tool_id'] == $toolId) {
                // Update dates instead
                $_SESSION['cart'][$key]['date_start'] = $dateStart;
                $_SESSION['cart'][$key]['date_end'] = $dateEnd;
                jsonResponse([
                    'success' => true, 
                    'message' => 'Datumi su ažurirani.',
                    'cart_count' => count($_SESSION['cart'])
                ]);
            }
        }
        
        // Add to cart
        $_SESSION['cart'][] = [
            'tool_id' => $toolId,
            'tool_name' => $tool['name'],
            'tool_slug' => $tool['slug'],
            'price_24h' => $tool['price_24h'],
            'date_start' => $dateStart,
            'date_end' => $dateEnd
        ];
        
        jsonResponse([
            'success' => true,
            'message' => 'Dodato u korpu.',
            'cart_count' => count($_SESSION['cart'])
        ]);
        break;
        
    case 'remove':
        $index = (int) ($input['index'] ?? -1);
        
        if (isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
            jsonResponse([
                'success' => true,
                'message' => 'Uklonjeno iz korpe.',
                'cart_count' => count($_SESSION['cart'])
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Stavka nije pronađena.'], 400);
        }
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        jsonResponse(['success' => true, 'message' => 'Korpa je ispražnjena.', 'cart_count' => 0]);
        break;
        
    case 'get':
        jsonResponse([
            'success' => true,
            'cart' => $_SESSION['cart'],
            'cart_count' => count($_SESSION['cart'])
        ]);
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Nepoznata akcija.'], 400);
}
