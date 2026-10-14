<?php

/**
 * View recent media upload debug logs (admin only).
 *
 * Visit: /media-upload-log.php?key=eternity-admin-2026
 * Also see: /storefront-images-diag.php?key=eternity-admin-2026
 * DELETE this file after debugging.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$key = $_GET['key'] ?? '';
$expected = 'eternity-admin-2026';

if (! hash_equals($expected, $key)) {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logDir = $basePath . '/storage/logs';
$files = glob($logDir . '/media-upload*.log') ?: [];
rsort($files);
$logFile = $files[0] ?? $logDir . '/media-upload.log';
$filter = $_GET['filter'] ?? 'all';

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Media Upload Log</title>';
echo '<style>body{font-family:Consolas,monospace;background:#111;color:#ddd;padding:20px}';
echo 'h1{font-family:Arial,sans-serif;color:#83B735}pre{background:#1e1e1e;padding:16px;overflow:auto;max-height:80vh;border:1px solid #333;line-height:1.4}';
echo '.warn{color:#ffb703}.err{color:#ff6b6b}.meta{color:#888;font-family:Arial,sans-serif;margin-bottom:16px}';
echo 'a{color:#83B735;font-family:Arial,sans-serif}</style></head><body>';
echo '<h1>Media Upload Debug Log</h1>';
echo '<p class="meta">File: <code>' . htmlspecialchars($logFile) . '</code><br>';
echo '<a href="?key=' . urlencode($key) . '&filter=all">All</a> &middot; ';
echo '<a href="?key=' . urlencode($key) . '&filter=storefront">Storefront only</a> &middot; ';
echo '<a href="?key=' . urlencode($key) . '&filter=upload">Upload only</a> &middot; ';
echo '<a href="/storefront-images-diag.php?key=' . urlencode($key) . '">Product image diagnostic</a></p>';

if (! is_readable($logFile)) {
    echo '<p class="warn">No log file yet. Upload a product image in admin and load the homepage, then refresh.</p>';
    echo '<p class="meta">Expected path: storage/logs/media-upload-YYYY-MM-DD.log</p>';
} else {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];

    if ($filter === 'storefront') {
        $lines = array_values(array_filter($lines, fn ($line) => str_contains($line, 'storefront.')));
    } elseif ($filter === 'upload') {
        $lines = array_values(array_filter($lines, fn ($line) => ! str_contains($line, 'storefront.')));
    }

    $tail = array_slice($lines, -250);
    $html = htmlspecialchars(implode("\n", $tail));
    $html = preg_replace('/\.(ERROR|WARNING)/', '<span class="err">.$1</span>', $html);
    $html = preg_replace('/storefront\.(product|media_url|homepage)[^\s]*/', '<span class="warn">$0</span>', $html);
    $html = preg_replace('/extract\.(failed|empty)|audit\.(no_path|file).*missing|sync\.skipped|setup_failed|NO product_images|FILE NOT ON DISK|missing_file/', '<span class="warn">$0</span>', $html);
    echo '<pre>' . ($html !== '' ? $html : '<span class="warn">No matching log lines for filter: ' . htmlspecialchars($filter) . '</span>') . '</pre>';
}

echo '</body></html>';
