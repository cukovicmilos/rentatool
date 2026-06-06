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
        $timeStart = $input['time_start'] ?? '08:00';
        $timeEnd = $input['time_end'] ?? '18:00';
        
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
        
        if ($start > $maxDate || $end > $maxDate) {
            jsonResponse(['success' => false, 'error' => 'Datumi moraju biti unutar ' . MAX_ADVANCE_DAYS . ' dana od danas.'], 400);
        }
        
        if ($end < $start) {
            jsonResponse(['success' => false, 'error' => 'Datum završetka mora biti posle datuma početka.'], 400);
        }
        
        // Check combined datetime
        $startTs = strtotime($dateStart . ' ' . $timeStart);
        $endTs = strtotime($dateEnd . ' ' . $timeEnd);
        if ($endTs <= $startTs) {
            jsonResponse(['success' => false, 'error' => 'Vreme završetka mora biti posle vremena početka.'], 400);
        }
        
        $actualHours = max(1, ($endTs - $startTs) / 3600);
        $days = max(1, (int) ceil($actualHours / 24));
        if ($days > MAX_RENTAL_DAYS) {
            jsonResponse(['success' => false, 'error' => 'Maksimalno ' . MAX_RENTAL_DAYS . ' dana.'], 400);
        }
        
        // Check for conflicting reservations (datetime overlap)
        $reqStartDT = $dateStart . ' ' . $timeStart;
        $reqEndDT = $dateEnd . ' ' . $timeEnd;
        $reqStartTs = strtotime($reqStartDT);
        $reqEndTs = strtotime($reqEndDT);
        
        $conflicts = db()->fetchAll("
            SELECT r.date_start, r.date_end, r.time_start, r.time_end
            FROM reservations r
            JOIN reservation_items ri ON r.id = ri.reservation_id
            WHERE ri.tool_id = ?
            AND r.status IN ('pending', 'confirmed', 'rented')
            AND r.date_end >= ? AND r.date_start <= ?
        ", [$toolId, $dateStart, $dateEnd]);
        
        foreach ($conflicts as $conflict) {
            $cTimeStart = $conflict['time_start'] ?? '08:00';
            $cTimeEnd = $conflict['time_end'] ?? '18:00';
            $confStartDT = $conflict['date_start'] . ' ' . $cTimeStart;
            $confEndDT = $conflict['date_end'] . ' ' . $cTimeEnd;
            
            if (strtotime($confStartDT) < $reqEndTs && strtotime($confEndDT) > $reqStartTs) {
                jsonResponse(['success' => false, 'error' => 'Alat je već rezervisan za odabrani termin.'], 400);
            }
        }
        
        // Check if tool already in cart
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['tool_id'] == $toolId) {
                // Update dates and times instead
                $_SESSION['cart'][$key]['date_start'] = $dateStart;
                $_SESSION['cart'][$key]['date_end'] = $dateEnd;
                $_SESSION['cart'][$key]['time_start'] = $timeStart;
                $_SESSION['cart'][$key]['time_end'] = $timeEnd;
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
            'date_end' => $dateEnd,
            'time_start' => $timeStart,
            'time_end' => $timeEnd
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
        
    case 'add_service':
        $serviceType = $input['service_type'] ?? '';
        $description = trim($input['description'] ?? '');
        $serviceDate = $input['service_date'] ?? '';
        $location = $input['location'] ?? '';
        
        $validTypes = ['drilling', 'cutting', 'assembly', 'gluing', 'repair', 'other'];
        $validLocations = ['workshop', 'onsite'];
        
        if (!in_array($serviceType, $validTypes)) {
            jsonResponse(['success' => false, 'error' => 'Nevažeća vrsta posla.'], 400);
        }
        if (empty($description)) {
            jsonResponse(['success' => false, 'error' => 'Opis posla je obavezan.'], 400);
        }
        if (empty($serviceDate)) {
            jsonResponse(['success' => false, 'error' => 'Željeni datum je obavezan.'], 400);
        }
        if (!in_array($location, $validLocations)) {
            jsonResponse(['success' => false, 'error' => 'Nevažeća lokacija.'], 400);
        }
        
        $date = strtotime($serviceDate);
        $today = strtotime('today');
        if ($date < $today) {
            jsonResponse(['success' => false, 'error' => 'Datum mora biti u budućnosti.'], 400);
        }
        
        $typeLabels = [
            'drilling' => 'Bušenje',
            'cutting' => 'Sečenje',
            'assembly' => 'Sastavljanje/Montaža',
            'gluing' => 'Lepljenje',
            'repair' => 'Popravka',
            'other' => 'Ostalo'
        ];
        
        $_SESSION['cart'][] = [
            'type' => 'service',
            'service_type' => $serviceType,
            'service_label' => 'Sitni poslovi - ' . $typeLabels[$serviceType],
            'description' => $description,
            'service_date' => $serviceDate,
            'location' => $location,
            'price' => 0
        ];
        
        jsonResponse([
            'success' => true,
            'message' => 'Usluga dodata u korpu.',
            'cart_count' => count($_SESSION['cart'])
        ]);
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Nepoznata akcija.'], 400);
}
