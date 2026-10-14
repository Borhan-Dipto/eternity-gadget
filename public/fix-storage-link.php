<?php

/**
 * Fix public/storage symlink on cPanel (one-time).
 *
 * Visit: https://eternitygadget.shop/fix-storage-link.php?key=eternity-admin-2026
 * DELETE this file after success.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$key = $_GET['key'] ?? '';
if (! hash_equals('eternity-admin-2026', $key)) {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$link = $basePath . '/public/storage';
$target = $basePath . '/storage/app/public';
$productsDir = $target . '/products';

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix Storage Link</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:720px;margin:24px auto;padding:0 16px}';
echo '.ok{color:#155724;background:#d4edda;padding:12px;border-radius:6px;margin:12px 0}';
echo '.fail{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;margin:12px 0}';
echo '.info{color:#333;background:#f5f5f5;padding:12px;border-radius:6px;margin:12px 0;font-family:Consolas,monospace;font-size:13px}';
echo '</style></head><body><h1>Fix Storage Symlink</h1>';

$lines = [];

if (! is_dir($target)) {
    mkdir($target, 0755, true);
    $lines[] = 'Created: ' . $target;
}

if (! is_dir($productsDir)) {
    mkdir($productsDir, 0755, true);
    $lines[] = 'Created: ' . $productsDir;
}

if (file_exists($link) && ! is_link($link)) {
    echo '<div class="fail"><strong>Problem:</strong> <code>public/storage</code> is a real folder, not a symlink.<br><br>';
    echo 'In cPanel File Manager, <strong>delete</strong> the folder <code>public/storage</code> (back it up first if it has files), then reload this page.</div>';
    echo '<div class="info">' . implode("\n", $lines) . '</div>';
    echo '</body></html>';
    exit;
}

if (is_link($link)) {
    @unlink($link);
    $lines[] = 'Removed old symlink';
}

$ok = @symlink($target, $link);

if (! $ok) {
    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Illuminate\Support\Facades\Artisan::call('storage:link');
    $lines[] = 'Artisan output: ' . trim(Illuminate\Support\Facades\Artisan::output());
    $ok = is_link($link);
}

if ($ok && realpath($link) === realpath($target)) {
    echo '<div class="ok"><strong>Success!</strong> public/storage now points to storage/app/public.</div>';
} else {
    echo '<div class="fail"><strong>Could not create symlink.</strong> Your host may block symlinks.<br>';
    echo 'Upload the latest code — it includes a Laravel fallback route for /storage/* URLs.</div>';
}

$sampleFiles = glob($productsDir . '/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE) ?: [];
$lines[] = 'Products folder file count: ' . count($sampleFiles);
if ($sampleFiles !== []) {
    $sample = basename($sampleFiles[0]);
    $lines[] = 'Sample file: ' . $sample;
    $lines[] = 'Test URL: /storage/products/' . $sample;
}

echo '<div class="info">' . nl2br(htmlspecialchars(implode("\n", $lines))) . '</div>';
echo '<p>Delete <code>public/fix-storage-link.php</code> after fixing.</p>';
echo '</body></html>';
