<?php
/**
 * Helper Functions
 */

/**
 * Generate URL with base path
 */
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Asset URL helper
 */
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Upload URL helper
 */
function upload(string $path): string {
    return url('uploads/' . ltrim($path, '/'));
}

/**
 * Redirect to URL
 */
function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

/**
 * Escape HTML output
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate slug from string
 */
function slugify(string $text): string {
    // Transliterate Serbian characters
    $transliteration = [
        'č' => 'c', 'ć' => 'c', 'đ' => 'dj', 'š' => 's', 'ž' => 'z',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'dj', 'Š' => 's', 'Ž' => 'z'
    ];
    $text = strtr($text, $transliteration);
    
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');
    
    // Replace non-alphanumeric with hyphens
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Trim hyphens
    return trim($text, '-');
}

/**
 * Format price
 */
function formatPrice(float $price): string {
    return number_format($price, 2, ',', '.') . ' €';
}

/**
 * Format date for display
 */
function formatDate(string $date): string {
    return date('d.m.Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(string $datetime): string {
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Check if date is weekend
 */
function isWeekend(string $date): bool {
    $dayOfWeek = date('N', strtotime($date));
    return $dayOfWeek >= 6; // 6 = Saturday, 7 = Sunday
}

/**
 * Calculate rental price
 */
function calculateRentalPrice(float $dailyPrice, array $dates): array {
    $totalDays = count($dates);
    $regularDays = 0;
    $weekendDays = 0;
    
    foreach ($dates as $date) {
        if (isWeekend($date)) {
            $weekendDays++;
        } else {
            $regularDays++;
        }
    }
    
    $regularTotal = $regularDays * $dailyPrice;
    $weekendTotal = $weekendDays * $dailyPrice * (1 + WEEKEND_MARKUP);
    $subtotal = $regularTotal + $weekendTotal;
    
    // Apply weekly discount
    $discount = 0;
    if ($totalDays >= 7) {
        $discount = $subtotal * WEEKLY_DISCOUNT;
    }
    
    $total = $subtotal - $discount;
    
    return [
        'total_days' => $totalDays,
        'regular_days' => $regularDays,
        'weekend_days' => $weekendDays,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total
    ];
}

/**
 * Generate unique reservation code
 */
function generateReservationCode(): string {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Check if user is logged in as admin
 */
function isAdmin(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin login
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect('admin/login');
    }
}

/**
 * Set flash message
 */
function flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get CSRF token
 */
function csrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF hidden input
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrf(): bool {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Get POST value with default
 */
function post(string $key, $default = '') {
    return $_POST[$key] ?? $default;
}

/**
 * Get GET value with default
 */
function get(string $key, $default = '') {
    return $_GET[$key] ?? $default;
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Upload image with resize
 */
function uploadImage(array $file, string $folder = ''): ?string {
    // Validate upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return null;
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return null;
    }
    
    // Create destination folder
    $destFolder = UPLOADS_PATH . ($folder ? '/' . $folder : '');
    if (!is_dir($destFolder)) {
        mkdir($destFolder, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $destFolder . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }
    
    // Resize image if needed
    resizeImage($destination, IMAGE_MAX_WIDTH, IMAGE_MAX_HEIGHT);
    
    return ($folder ? $folder . '/' : '') . $filename;
}

/**
 * Resize image maintaining aspect ratio
 */
function resizeImage(string $path, int $maxWidth, int $maxHeight): bool {
    $info = getimagesize($path);
    if (!$info) {
        return false;
    }
    
    list($width, $height, $type) = $info;
    
    // No resize needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int) ($width * $ratio);
    $newHeight = (int) ($height * $ratio);
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($path);
            break;
        default:
            return false;
    }
    
    // Create resized image
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }
    
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($resized, $path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($resized, $path, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($resized, $path, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($resized);
    
    return true;
}

/**
 * Delete uploaded file
 */
function deleteUpload(string $path): bool {
    $fullPath = UPLOADS_PATH . '/' . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Get dates between two dates
 */
function getDatesBetween(string $start, string $end): array {
    $dates = [];
    $current = strtotime($start);
    $endTime = strtotime($end);
    
    while ($current <= $endTime) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    
    return $dates;
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send Telegram notification
 * 
 * @param string $message Message to send (supports HTML formatting)
 * @return bool True on success, false on failure
 */
function sendTelegramNotification(string $message): bool {
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = TELEGRAM_CHAT_ID;
    
    // Skip if not configured
    if (empty($botToken) || empty($chatId)) {
        error_log('Telegram notification skipped: Bot token or chat ID not configured');
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    // Use cURL for the request
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Telegram notification error: {$error}");
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("Telegram notification failed with HTTP {$httpCode}: {$response}");
        return false;
    }
    
    return true;
}

/**
 * Format reservation for Telegram notification
 * 
 * @param array $reservation Reservation data
 * @param array $items Reservation items with tool details
 * @return string Formatted message
 */
function formatReservationTelegramMessage(array $reservation, array $items): string {
    $message = "<b>🔧 Nova rezervacija!</b>\n\n";
    $message .= "<b>Broj:</b> #{$reservation['code']}\n";
    $message .= "<b>Mušterija:</b> {$reservation['customer_name']}\n";
    $message .= "<b>Telefon:</b> {$reservation['customer_phone']}\n";
    
    if (!empty($reservation['customer_email'])) {
        $message .= "<b>Email:</b> {$reservation['customer_email']}\n";
    }
    
    $message .= "\n<b>Period:</b> " . formatDate($reservation['date_start']) . " - " . formatDate($reservation['date_end']) . "\n";
    
    // Delivery option
    $deliveryOptions = [
        'pickup' => 'Lično preuzimanje',
        'delivery' => 'Dostava',
        'roundtrip' => 'Dostava + povratak'
    ];
    $message .= "<b>Dostava:</b> " . ($deliveryOptions[$reservation['delivery_option']] ?? $reservation['delivery_option']) . "\n";
    
    if (!empty($reservation['delivery_address'])) {
        $message .= "<b>Adresa:</b> {$reservation['delivery_address']}\n";
    }
    
    $message .= "\n<b>Alati:</b>\n";
    foreach ($items as $item) {
        $message .= "• {$item['tool_name']} - " . formatPrice($item['price']) . "\n";
    }
    
    $message .= "\n<b>Ukupno:</b> " . formatPrice($reservation['total_price']);
    
    if ($reservation['deposit_total'] > 0) {
        $message .= "\n<b>Depozit:</b> " . formatPrice($reservation['deposit_total']);
    }
    
    if (!empty($reservation['notes'])) {
        $message .= "\n\n<b>Napomena:</b> {$reservation['notes']}";
    }
    
    return $message;
}
