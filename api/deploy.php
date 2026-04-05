<?php
/**
 * GitHub Webhook Endpoint
 * 
 * Usage: Add webhook on GitHub pointing to:
 * https://rentatool.in.rs/api/deploy?secret=YOUR_SECRET
 * 
 * Triggers post-receive hook after push
 */

$secret = getenv('GITHUB_WEBHOOK_SECRET') ?: '';

$providedSecret = $_GET['secret'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Simple secret check (query param or header)
if ($secret && $providedSecret !== $secret) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Verify it's a push event
$githubEvent = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($githubEvent !== 'push') {
    http_response_code(200);
    echo "Ignored - not a push event";
    exit;
}

// Run deploy
$output = [];
$returnCode = 0;

exec('cd /var/www/rentatool && bash .git/hooks/post-receive 2>&1', $output, $returnCode);

header('Content-Type: text/plain');
echo "Deploy triggered. Return code: $returnCode\n";
echo implode("\n", $output);
