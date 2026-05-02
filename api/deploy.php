<?php
/**
 * GitHub Webhook Endpoint
 * 
 * Usage: Add webhook on GitHub pointing to:
 * https://rentatool.in.rs/api/deploy?secret=YOUR_SECRET
 * 
 * Pulls latest code, reloads PHP-FPM, runs smoke test
 */

$secret = getenv('GITHUB_WEBHOOK_SECRET') ?: '';
$providedSecret = $_GET['secret'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($secret && $providedSecret !== $secret) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$githubEvent = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($githubEvent !== 'push') {
    http_response_code(200);
    echo "Ignored - not a push event";
    exit;
}

header('Content-Type: text/plain');
$log = [];

$log[] = date('Y-m-d H:i:s') . ' - Deploy started';

$appDir = '/var/www/rentatool';

chdir($appDir);
exec('git fetch origin 2>&1', $fetchOut, $fetchCode);
$log[] = 'Fetch: ' . implode("\n", $fetchOut);
exec('git reset --hard origin/main 2>&1', $resetOut, $resetCode);
$log[] = 'Reset: ' . implode("\n", $resetOut);

if ($resetCode !== 0) {
    $log[] = 'Git pull failed, aborting deploy';
    @file_put_contents($appDir . '/temp/deploy.log', implode("\n", $log) . "\n", FILE_APPEND);
    echo implode("\n", $log);
    exit(1);
}

$log[] = 'Reloading PHP-FPM...';
exec('sudo systemctl reload php8.4-fpm 2>&1', $reloadOut, $reloadCode);
$log[] = 'Reload: ' . implode("\n", $reloadOut);

if ($reloadCode !== 0) {
    $log[] = 'systemctl reload failed, trying opcache_reset...';
    exec('php -r "opcache_reset();" 2>&1', $opcacheOut, $opcacheCode);
    $log[] = 'opcache_reset: ' . implode("\n", $opcacheOut);
}

sleep(2);

$log[] = 'Running smoke test...';
exec('bash scripts/smoke-test.sh 2>&1', $testOut, $testCode);
$log = array_merge($log, $testOut);

if ($testCode === 0) {
    $log[] = date('Y-m-d H:i:s') . ' - SMOKE TEST PASSED';
} else {
    $log[] = date('Y-m-d H:i:s') . ' - SMOKE TEST FAILED (code: ' . $testCode . ')';
    
    $botToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';
    $chatId = getenv('TELEGRAM_CHAT_ID') ?: '';
    if ($botToken && $chatId) {
        $msg = urlencode("Deploy failed! Smoke test not passed. Check server.");
        @file_get_contents("https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$msg}");
    }
}

@file_put_contents($appDir . '/temp/deploy.log', implode("\n", $log) . "\n", FILE_APPEND);

echo implode("\n", $log);

