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

// Run post-receive hook from scripts folder
exec('cd /var/www/rentatool && bash scripts/post-receive-hook.sh 2>&1', $output, $returnCode);

// Log output to file for debugging
$logMsg = date('Y-m-d H:i:s') . " - Deploy called. Return code: $returnCode\n";
@file_put_contents('/var/www/rentatool/temp/deploy.log', $logMsg, FILE_APPEND);

header('Content-Type: text/plain');
echo "Deploy triggered. Return code: $returnCode\n";
echo implode("\n", $output);
