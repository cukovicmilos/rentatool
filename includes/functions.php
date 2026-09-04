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
    $filePath = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $version = file_exists($filePath) ? filemtime($filePath) : time();
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
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
function redirect(string $path, int $statusCode = 302): void {
    header('Location: ' . url($path), true, $statusCode);
    exit;
}

/**
 * Escape HTML output
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate alt text for tool images
 */
function toolAlt(array $tool): string {
    return ($tool['name'] ?? '') . ' - Iznajmljivanje alata u Subotici - Rent a Tool';
}

/**
 * Generate Schema.org Product JSON-LD array for a tool
 */
function productSchema(array $tool): array {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $tool['name'] ?? '',
        'description' => $tool['short_description'] ?? $tool['description'] ?? ($tool['name'] ?? ''),
        'sku' => 'TOOL-' . ($tool['id'] ?? '0'),
        'brand' => [
            '@type' => 'Brand',
            'name' => SITE_NAME
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => 'https://rentatool.in.rs' . BASE_URL . '/alat/' . ($tool['slug'] ?? ''),
            'priceCurrency' => CURRENCY,
            'price' => number_format($tool['price_24h'] ?? 0, 2, '.', ''),
            'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
            'availability' => ($tool['status'] ?? '') === 'available'
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
            'seller' => [
                '@type' => 'LocalBusiness',
                'name' => SITE_NAME,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Subotica',
                    'addressCountry' => 'RS'
                ]
            ],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => number_format(DELIVERY_ONEWAY, 2, '.', ''),
                    'currency' => CURRENCY
                ],
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => 'RS'
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'DAY'
                    ],
                    'transitTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'DAY'
                    ]
                ]
            ],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => 'RS',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => MAX_RENTAL_DAYS,
                'returnMethod' => 'https://schema.org/ReturnInStore',
                'returnFees' => 'https://schema.org/FreeReturn'
            ]
        ]
    ];

    if (!empty($tool['primary_image'])) {
        $schema['image'] = 'https://rentatool.in.rs' . BASE_URL . '/uploads/tools/' . $tool['primary_image'];
    }

    return $schema;
}

/**
 * Generate Schema.org ItemList JSON-LD array for a list of tools
 */
function itemListSchema(array $tools, ?string $listName = null): array {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => []
    ];

    if ($listName) {
        $schema['name'] = $listName;
    }

    $position = 1;
    foreach ($tools as $tool) {
        $item = [
            '@type' => 'ListItem',
            'position' => $position++,
            'url' => 'https://rentatool.in.rs' . BASE_URL . '/alat/' . ($tool['slug'] ?? ''),
            'name' => $tool['name'] ?? ''
        ];
        if (!empty($tool['primary_image'])) {
            $item['image'] = 'https://rentatool.in.rs' . BASE_URL . '/uploads/tools/' . $tool['primary_image'];
        }
        $schema['itemListElement'][] = $item;
    }

    return $schema;
}

/**
 * Normalize Serbian text (remove diacritics for search)
 */
function normalizeSerbian(string $text): string {
    $transliteration = [
        'č' => 'c', 'ć' => 'c', 'đ' => 'dj', 'š' => 's', 'ž' => 'z',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'dj', 'Š' => 's', 'Ž' => 'z'
    ];
    $text = strtr($text, $transliteration);
    return mb_strtolower($text, 'UTF-8');
}

/**
 * Generate SQL expression to normalize a column for Serbian diacritic-insensitive search
 */
function sqlNormalizeSerbian(string $column): string {
    return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column},'š','s'),'đ','dj'),'č','c'),'ć','c'),'ž','z'))";
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
 * Format rental duration for display (e.g. "6h", "1 dan 16h", "3 dana")
 */
function formatRentalDuration(int $totalHours): string {
    if ($totalHours < 24) {
        return $totalHours . 'h';
    }
    $days = floor($totalHours / 24);
    $hours = $totalHours % 24;
    $dayStr = $days == 1 ? 'dan' : 'dana';
    if ($hours > 0) {
        return $days . ' ' . $dayStr . ' ' . $hours . 'h';
    }
    return $days . ' ' . $dayStr;
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
 * Calculate total rental hours between two datetimes
 */
function calculateRentalHours(string $dateStart, string $dateEnd, string $timeStart = '08:00', string $timeEnd = '18:00'): int {
    $startTs = strtotime($dateStart . ' ' . $timeStart);
    $endTs = strtotime($dateEnd . ' ' . $timeEnd);
    return max(1, (int) ceil(($endTs - $startTs) / 3600));
}

/**
 * Calculate rental days based on actual hours (24h = 1 day, full 24h blocks)
 */
function calculateRentalDays(string $dateStart, string $dateEnd, string $timeStart = '08:00', string $timeEnd = '18:00'): int {
    $hours = calculateRentalHours($dateStart, $dateEnd, $timeStart, $timeEnd);
    return max(1, (int) ceil($hours / 24));
}

/**
 * Calculate rental price
 */
function calculateRentalPrice(float $dailyPrice, array $dates, ?string $dateStart = null, ?string $dateEnd = null, ?string $timeStart = null, ?string $timeEnd = null): array {
    // Calculate actual days if time info provided
    $totalHours = 0;
    if ($dateStart && $dateEnd) {
        $startTs = strtotime($dateStart . ' ' . ($timeStart ?? '08:00'));
        $endTs = strtotime($dateEnd . ' ' . ($timeEnd ?? '18:00'));
        $totalHours = max(1, (int) ceil(($endTs - $startTs) / 3600));
        $totalDays = max(1, (int) ceil($totalHours / 24));
    } else {
        $totalDays = count($dates);
        $totalHours = $totalDays * 24;
    }
    
    $regularDays = 0;
    $weekendDays = 0;
    
    // Count weekend days only from the billed days
    $countedDates = array_slice($dates, 0, $totalDays);
    foreach ($countedDates as $date) {
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
        'total_hours' => $totalHours,
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

    // Generate WebP version for performance
    generateWebP($destination);

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
 * Generate WebP version of an image
 */
function generateWebP(string $path, int $quality = 80): ?string {
    $info = getimagesize($path);
    if (!$info) return null;

    $type = $info[2];
    switch ($type) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($path); break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($path);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            break;
        default: return null;
    }

    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    imagewebp($img, $webpPath, $quality);
    imagedestroy($img);

    return $webpPath;
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
 * Verify hCaptcha token
 *
 * @param string $response hCaptcha response token
 * @param string|null $remoteIp Optional remote IP address
 * @return bool True if verification succeeds
 */
function verifyHcaptcha(string $response, ?string $remoteIp = null): bool {
    $secret = HCAPTCHA_SECRET_KEY;

    // If no secret key is configured, fail closed unless explicitly disabled
    if (empty($secret)) {
        error_log('hCaptcha verification skipped: secret key not configured');
        return false;
    }

    if (empty($response)) {
        return false;
    }

    $data = [
        'secret' => $secret,
        'response' => $response,
    ];

    if ($remoteIp) {
        $data['remoteip'] = $remoteIp;
    }

    $ch = curl_init('https://hcaptcha.com/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("hCaptcha verification error: {$error}");
        return false;
    }

    $json = json_decode($result, true);
    return !empty($json['success']);
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
    
    $timeStart = $reservation['time_start'] ?? '';
    $timeEnd = $reservation['time_end'] ?? '';
    $timeStr = '';
    if ($timeStart || $timeEnd) {
        $timeStr = " {$timeStart}h - {$timeEnd}h";
    }
    $message .= "\n<b>Period:</b> " . formatDate($reservation['date_start']) . " - " . formatDate($reservation['date_end']) . "{$timeStr}\n";
    
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
    
    $message .= "\n<b>Alati/Usluge:</b>\n";
    foreach ($items as $item) {
        if (isset($item['type']) && $item['type'] === 'service') {
            $locationText = $item['location'] === 'workshop' ? 'Radionica' : 'Na adresi';
            $message .= "🔧 <b>USLUGA:</b> {$item['tool_name']}\n";
            if (!empty($item['description'])) {
                $message .= "   Opis: " . str_replace("\n", "\n   ", $item['description']) . "\n";
            }
            if (!empty($item['service_date'])) {
                $message .= "   Datum: " . formatDate($item['service_date']) . "\n";
            }
            $message .= "   Lokacija: {$locationText}\n";
        } else {
            $message .= "• {$item['tool_name']} - " . formatPrice($item['price']) . "\n";
        }
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

/**
 * Get Serbian label for reservation status
 */
function reservationStatusLabel(string $status): string {
    $labels = [
        'pending' => 'Na čekanju',
        'confirmed' => 'Potvrđena',
        'rented' => 'Iznajmljeno',
        'completed' => 'Završena',
        'cancelled' => 'Otkazana',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Extract YouTube video ID from URL
 */
function getYouTubeVideoId(string $url): ?string {
    if (empty($url)) {
        return null;
    }
    
    // Parse different YouTube URL formats
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',      // https://www.youtube.com/watch?v=VIDEO_ID
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',        // https://www.youtube.com/embed/VIDEO_ID
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',                  // https://youtu.be/VIDEO_ID
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',            // https://www.youtube.com/v/VIDEO_ID
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/* ==========================================================================
   Bundle helpers
   ========================================================================== */

/**
 * Check if a tool row represents a bundle.
 */
function isBundle(array $tool): bool {
    return ($tool['type'] ?? 'tool') === 'bundle';
}

/**
 * Get component tools for a bundle (with primary image).
 */
function getBundleItems(int $bundleId): array {
    return db()->fetchAll("
        SELECT t.id, t.name, t.slug, t.price_24h, t.deposit, t.status,
               (SELECT filename FROM tool_images WHERE tool_id = t.id AND is_primary = 1 LIMIT 1) as primary_image,
               bi.sort_order
        FROM bundle_items bi
        JOIN tools t ON t.id = bi.component_id
        WHERE bi.bundle_id = ?
        ORDER BY bi.sort_order, t.name
    ", [$bundleId]);
}

/**
 * Get component tool IDs for a bundle.
 */
function getBundleToolIds(int $bundleId): array {
    $rows = db()->fetchAll("SELECT component_id FROM bundle_items WHERE bundle_id = ? ORDER BY sort_order", [$bundleId]);
    return array_column($rows, 'component_id');
}

/**
 * Recalculate bundle price as sum of component prices minus 10%.
 * Bundle deposit is always 0.
 */
function recalculateBundlePrice(int $bundleId): void {
    $total = (float) db()->fetchColumn("
        SELECT COALESCE(SUM(t.price_24h), 0)
        FROM bundle_items bi
        JOIN tools t ON t.id = bi.component_id
        WHERE bi.bundle_id = ?
    ", [$bundleId]);

    $bundlePrice = round($total * 0.9, 2);
    db()->execute(
        "UPDATE tools SET price_24h = ?, deposit = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$bundlePrice, $bundleId]
    );
}

/**
 * Center-crop and resize a GD image resource to exact dimensions.
 */
function bundleCenterCrop($source, int $targetWidth, int $targetHeight) {
    $srcW = imagesx($source);
    $srcH = imagesy($source);
    $ratio = max($targetWidth / $srcW, $targetHeight / $srcH);
    $newW = (int) ($srcW * $ratio);
    $newH = (int) ($srcH * $ratio);

    $resized = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    $crop = imagecreatetruecolor($targetWidth, $targetHeight);
    $x = (int) (($newW - $targetWidth) / 2);
    $y = (int) (($newH - $targetHeight) / 2);
    imagecopy($crop, $resized, 0, 0, $x, $y, $targetWidth, $targetHeight);
    imagedestroy($resized);

    return $crop;
}

/**
 * Generate a collage image for a bundle from component primary images.
 * Saves JPG + WebP to uploads/tools/ and sets it as the bundle's primary image.
 * Returns the generated filename or null.
 */
function generateBundleCollage(int $bundleId): ?string {
    $items = getBundleItems($bundleId);
    if (empty($items)) {
        return null;
    }

    $images = [];
    foreach ($items as $item) {
        if (empty($item['primary_image'])) {
            continue;
        }
        $path = UPLOADS_PATH . '/tools/' . $item['primary_image'];
        $info = @getimagesize($path);
        if (!$info) {
            continue;
        }

        $img = null;
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($path);
                break;
            case IMAGETYPE_WEBP:
                $img = imagecreatefromwebp($path);
                break;
        }

        if ($img) {
            $images[] = $img;
        }
    }

    if (empty($images)) {
        return null;
    }

    $width = 800;
    $height = 600;
    $canvas = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($canvas, 240, 240, 240);
    imagefill($canvas, 0, 0, $bg);

    $count = count($images);
    $gap = 4;

    if ($count === 1) {
        $thumb = bundleCenterCrop($images[0], $width, $height);
        imagecopy($canvas, $thumb, 0, 0, 0, 0, $width, $height);
        imagedestroy($thumb);
    } elseif ($count === 2) {
        $w = (int) (($width - $gap) / 2);
        $thumb = bundleCenterCrop($images[0], $w, $height);
        imagecopy($canvas, $thumb, 0, 0, 0, 0, $w, $height);
        imagedestroy($thumb);
        $thumb = bundleCenterCrop($images[1], $w, $height);
        imagecopy($canvas, $thumb, $w + $gap, 0, 0, 0, $w, $height);
        imagedestroy($thumb);
    } elseif ($count === 3) {
        $w = (int) (($width - $gap) / 2);
        $h = (int) (($height - $gap) / 2);
        $thumb = bundleCenterCrop($images[0], $w, $h);
        imagecopy($canvas, $thumb, 0, 0, 0, 0, $w, $h);
        imagedestroy($thumb);
        $thumb = bundleCenterCrop($images[1], $w, $h);
        imagecopy($canvas, $thumb, $w + $gap, 0, 0, 0, $w, $h);
        imagedestroy($thumb);
        $thumb = bundleCenterCrop($images[2], $width, $h);
        imagecopy($canvas, $thumb, 0, $h + $gap, 0, 0, $width, $h);
        imagedestroy($thumb);
    } else {
        // 4+ components -> 2x2 grid using first four
        $w = (int) (($width - $gap) / 2);
        $h = (int) (($height - $gap) / 2);
        for ($i = 0; $i < min(4, $count); $i++) {
            $thumb = bundleCenterCrop($images[$i], $w, $h);
            $x = ($i % 2) * ($w + $gap);
            $y = (int) floor($i / 2) * ($h + $gap);
            imagecopy($canvas, $thumb, $x, $y, 0, 0, $w, $h);
            imagedestroy($thumb);
        }
    }

    foreach ($images as $img) {
        imagedestroy($img);
    }

    $filename = 'bundle-collage-' . $bundleId . '-' . uniqid() . '.jpg';
    $destPath = UPLOADS_PATH . '/tools/' . $filename;
    imagejpeg($canvas, $destPath, 90);
    imagedestroy($canvas);

    generateWebP($destPath);

    // Set as primary image for the bundle
    db()->execute("UPDATE tool_images SET is_primary = 0 WHERE tool_id = ?", [$bundleId]);
    db()->insert(
        "INSERT INTO tool_images (tool_id, filename, sort_order, is_primary) VALUES (?, ?, 0, 1)",
        [$bundleId, $filename]
    );

    // Remove previous collage images, keeping the one we just created
    deleteBundleCollageImages($bundleId, $filename);

    return $filename;
}

/**
 * Delete auto-generated bundle collage images for a bundle.
 * If $keepFilename is provided, that file is preserved.
 */
function deleteBundleCollageImages(int $bundleId, ?string $keepFilename = null): void {
    $images = db()->fetchAll(
        "SELECT id, filename FROM tool_images WHERE tool_id = ? AND filename LIKE 'bundle-collage-%'",
        [$bundleId]
    );

    foreach ($images as $img) {
        if ($keepFilename && $img['filename'] === $keepFilename) {
            continue;
        }

        deleteUpload('tools/' . $img['filename']);
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $img['filename']);
        if ($webp !== $img['filename']) {
            deleteUpload('tools/' . $webp);
        }

        db()->execute("DELETE FROM tool_images WHERE id = ?", [$img['id']]);
    }
}

/**
 * Recalculate price and regenerate collage for every bundle that contains a tool.
 */
function recalculateAllBundlesForTool(int $toolId): void {
    $bundles = db()->fetchAll("SELECT bundle_id FROM bundle_items WHERE component_id = ?", [$toolId]);
    foreach ($bundles as $bundle) {
        recalculateBundlePrice($bundle['bundle_id']);
        generateBundleCollage($bundle['bundle_id']);
    }
}

/**
 * Check whether all components of a bundle are available for the requested period.
 * Returns an error string or null if available.
 */
function checkBundleAvailability(int $bundleId, string $dateStart, string $dateEnd, string $timeStart, string $timeEnd): ?string {
    $components = getBundleItems($bundleId);
    if (empty($components)) {
        return 'Bundle ne sadrži alate.';
    }

    foreach ($components as $component) {
        if ($component['status'] !== 'available') {
            return 'Alat "' . $component['name'] . '" iz bundle-a trenutno nije dostupan.';
        }
    }

    $toolIds = getBundleToolIds($bundleId);
    if (empty($toolIds)) {
        return null;
    }

    // Check blocked dates for any component
    $dates = getDatesBetween($dateStart, $dateEnd);
    if (!empty($dates)) {
        $idPlaceholders = implode(',', array_fill(0, count($toolIds), '?'));
        $datePlaceholders = implode(',', array_fill(0, count($dates), '?'));
        $blocked = db()->fetchAll(
            "SELECT blocked_date FROM blocked_dates
             WHERE (tool_id IN ({$idPlaceholders}) OR tool_id IS NULL)
             AND blocked_date IN ({$datePlaceholders})",
            array_merge($toolIds, $dates)
        );
        if (!empty($blocked)) {
            return 'Neki od alata u bundle-u nisu dostupni za odabrane datume.';
        }
    }

    // Check conflicting reservations for each component
    $reqStartTs = strtotime($dateStart . ' ' . $timeStart);
    $reqEndTs = strtotime($dateEnd . ' ' . $timeEnd);
    $idPlaceholders = implode(',', array_fill(0, count($toolIds), '?'));
    $conflicts = db()->fetchAll("
        SELECT r.date_start, r.date_end, r.time_start, r.time_end, t.name as tool_name
        FROM reservations r
        JOIN reservation_items ri ON r.id = ri.reservation_id
        JOIN tools t ON t.id = ri.tool_id
        WHERE ri.tool_id IN ({$idPlaceholders})
          AND r.status IN ('pending', 'confirmed', 'rented')
          AND r.date_end >= ? AND r.date_start <= ?
    ", array_merge($toolIds, [$dateStart, $dateEnd]));

    foreach ($conflicts as $conflict) {
        $cTimeStart = $conflict['time_start'] ?? '08:00';
        $cTimeEnd = $conflict['time_end'] ?? '18:00';
        $confStartTs = strtotime($conflict['date_start'] . ' ' . $cTimeStart);
        $confEndTs = strtotime($conflict['date_end'] . ' ' . $cTimeEnd);

        if ($confStartTs < $reqEndTs && $confEndTs > $reqStartTs) {
            return 'Alat "' . $conflict['tool_name'] . '" iz bundle-a je već rezervisan za deo odabranog termina.';
        }
    }

    return null;
}
