<?php

require_once __DIR__ . '/config.php';

function googleCalendarAuthenticate(): ?string
{
    $credDir = GOOGLE_CREDENTIALS_PATH;
    if (!is_dir($credDir)) {
        error_log('Google Calendar: credentials directory not found: ' . $credDir);
        return null;
    }

    $files = glob($credDir . '/*.json');
    if (empty($files)) {
        error_log('Google Calendar: no JSON credential file found in ' . $credDir);
        return null;
    }

    $credJson = file_get_contents($files[0]);
    $cred = json_decode($credJson, true);

    if (!$cred || !isset($cred['client_email'], $cred['private_key'])) {
        error_log('Google Calendar: invalid credential file');
        return null;
    }

    $now = time();
    $header = base64url(['alg' => 'RS256', 'typ' => 'JWT']);
    $payload = base64url([
        'iss' => $cred['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ]);

    $signature = '';
    $privateKey = openssl_get_privatekey($cred['private_key']);
    if (!$privateKey) {
        error_log('Google Calendar: failed to load private key');
        return null;
    }
    openssl_sign("{$header}.{$payload}", $signature, $privateKey, 'sha256WithRSAEncryption');
    openssl_free_key($privateKey);

    $jwt = "{$header}.{$payload}." . base64url($signature);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Google Calendar auth error: {$error}");
        return null;
    }

    if ($httpCode !== 200) {
        error_log("Google Calendar auth failed (HTTP {$httpCode}): {$response}");
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['access_token'])) {
        error_log('Google Calendar: no access_token in response');
        return null;
    }

    return $data['access_token'];
}

function googleCalendarApiCall(string $method, string $endpoint, ?array $body = null): ?array
{
    $calendarId = GOOGLE_CALENDAR_ID;
    if (empty($calendarId)) {
        error_log('Google Calendar: CALENDAR_ID not configured');
        return null;
    }

    $token = googleCalendarAuthenticate();
    if (!$token) {
        return null;
    }

    $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . $endpoint;

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    $curlOpts = [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ];

    if ($method === 'POST') {
        $curlOpts[CURLOPT_POST] = true;
        $curlOpts[CURLOPT_POSTFIELDS] = json_encode($body);
    } elseif ($method === 'PUT') {
        $curlOpts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $curlOpts[CURLOPT_POSTFIELDS] = json_encode($body);
    } elseif ($method === 'DELETE') {
        $curlOpts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }

    curl_setopt_array($ch, $curlOpts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Google Calendar API error ({$method} {$endpoint}): {$error}");
        return null;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Google Calendar API failed (HTTP {$httpCode}): {$response}");
        return null;
    }

    if (empty($response)) {
        return [];
    }

    $data = json_decode($response, true);
    return $data ?: [];
}

function googleCalendarBuildEvent(array $reservation, array $items): array
{
    $toolNames = array_map(function ($item) {
        return $item['tool_name'];
    }, array_filter($items, function ($item) {
        return ($item['item_type'] ?? 'tool') === 'tool';
    }));

    $toolsStr = implode(', ', $toolNames);

    $timezone = 'Europe/Belgrade';

    $startDateTime = $reservation['date_start'] . 'T' . ($reservation['time_start'] ?? '08:00') . ':00';
    $endDateTime = $reservation['date_end'] . 'T' . ($reservation['time_end'] ?? '18:00') . ':00';

    $itemsDesc = '';
    foreach ($items as $item) {
        if (($item['item_type'] ?? 'tool') === 'tool') {
            $itemsDesc .= "\n- {$item['tool_name']} ({$item['price_per_day']}" . CURRENCY_SIGN . "/dan)";
        }
    }

    $description = "Kod: {$reservation['reservation_code']}"
        . "\nKupac: {$reservation['customer_name']}"
        . "\nTelefon: {$reservation['customer_phone']}"
        . ($reservation['customer_email'] ? "\nEmail: {$reservation['customer_email']}" : '')
        . "\n\nAlati:{$itemsDesc}"
        . "\n\nPeriod: " . formatDate($reservation['date_start']) . ' - ' . formatDate($reservation['date_end'])
        . ' (' . e($reservation['time_start'] ?? '08:00') . 'h - ' . e($reservation['time_end'] ?? '18:00') . 'h)'
        . "\nUkupno: " . formatPrice($reservation['total']);

    $summary = $reservation['customer_name'] . ' - ' . $toolsStr;

    return [
        'summary' => $summary,
        'description' => $description,
        'start' => [
            'dateTime' => $startDateTime,
            'timeZone' => $timezone,
        ],
        'end' => [
            'dateTime' => $endDateTime,
            'timeZone' => $timezone,
        ],
    ];
}

function googleCalendarCreateEvent(array $reservation, array $items): ?string
{
    $event = googleCalendarBuildEvent($reservation, $items);
    $result = googleCalendarApiCall('POST', '/events', $event);

    if ($result && isset($result['id'])) {
        db()->execute(
            "UPDATE reservations SET google_event_id = ? WHERE id = ?",
            [$result['id'], $reservation['id']]
        );
        return $result['id'];
    }

    return null;
}

function googleCalendarUpdateEvent(array $reservation, array $items): bool
{
    if (empty($reservation['google_event_id'])) {
        return false;
    }

    $eventId = urlencode($reservation['google_event_id']);
    $event = googleCalendarBuildEvent($reservation, $items);
    $result = googleCalendarApiCall('PUT', "/events/{$eventId}", $event);

    return $result !== null;
}

function base64url($data): string
{
    if (is_array($data)) {
        $data = json_encode($data);
    }
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
