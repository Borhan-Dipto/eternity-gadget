<?php

/**
 * Storefront product image diagnostic (admin only).
 *
 * Visit: /storefront-images-diag.php?key=eternity-admin-2026
 * DELETE this file after debugging.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$key = $_GET['key'] ?? '';
if (! hash_equals('eternity-admin-2026', $key)) {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
$app = require $basePath . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Support\MediaUploadLogger;
use Illuminate\Support\Facades\Storage;

$limit = max(5, min(50, (int) ($_GET['limit'] ?? 25)));

$products = Product::query()
    ->with(['images', 'category'])
    ->orderByDesc('created_at')
    ->limit($limit)
    ->get();

$storageReport = MediaUploadLogger::auditStorageSetup();

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Storefront Image Diagnostic</title>';
echo '<style>';
echo 'body{font-family:Arial,sans-serif;background:#111;color:#eee;padding:20px;max-width:1200px;margin:0 auto}';
echo 'h1{color:#83B735}h2{color:#ccc;margin-top:28px}table{width:100%;border-collapse:collapse;margin:16px 0;font-size:13px}';
echo 'th,td{border:1px solid #333;padding:8px;text-align:left;vertical-align:top}th{background:#222}';
echo '.ok{color:#52b788}.warn{color:#ffb703}.err{color:#ff6b6b}.meta{color:#888;margin-bottom:20px}';
echo 'code{background:#222;padding:2px 5px;border-radius:3px;font-size:12px}img.thumb{max-width:64px;max-height:64px;background:#222}';
echo 'a{color:#83B735}.box{background:#1a1a1a;border:1px solid #333;padding:12px;border-radius:6px;margin:12px 0}';
echo '</style></head><body>';

echo '<h1>Storefront Image Diagnostic</h1>';
echo '<p class="meta">Shows why product images may not appear on the main website. ';
echo '<a href="/media-upload-log.php?key=eternity-admin-2026">View upload log</a> &middot; ';
echo '<a href="/fix-storage-link.php?key=eternity-admin-2026">Fix storage symlink</a></p>';

echo '<div class="box"><strong>Storage environment</strong><br>';
echo 'Symlink exists: ' . ($storageReport['symlink_exists'] ? 'yes' : 'no') . '<br>';
echo 'Symlink is link: ' . ($storageReport['symlink_is_link'] ? 'yes' : '<span class="err">NO — real folder or missing</span>') . '<br>';
echo 'products/ writable: ' . ($storageReport['products_dir_writable'] ? 'yes' : '<span class="err">NO</span>') . '<br>';
echo 'Path: <code>' . htmlspecialchars((string) $storageReport['public_storage_path']) . '</code></div>';

echo '<p class="meta">Tip: load the <a href="/">homepage</a> once, then refresh this page and the upload log.</p>';

echo '<h2>Latest ' . $products->count() . ' products</h2>';
echo '<table><thead><tr>';
echo '<th>ID</th><th>Preview</th><th>Product</th><th>Flags</th><th>DB images</th><th>Primary path</th><th>File on disk</th><th>Status</th>';
echo '</tr></thead><tbody>';

foreach ($products as $product) {
    $audit = MediaUploadLogger::auditStorefrontProduct($product, 'diag.page');
    $statusClass = match ($audit['status']) {
        'ok', 'external_url' => 'ok',
        'symlink', 'no_image' => 'warn',
        default => 'err',
    };

    $thumb = $audit['primary_path'] && ($audit['storage_exists'] ?? false)
        ? MediaUploadLogger::storageUrl($audit['primary_path'])
        : asset('images/placeholder.svg');

    echo '<tr>';
    echo '<td>' . (int) $product->id . '</td>';
    echo '<td><img class="thumb" src="' . htmlspecialchars($thumb) . '" alt=""></td>';
    echo '<td><strong>' . htmlspecialchars($product->name) . '</strong><br><code>' . htmlspecialchars($product->slug) . '</code></td>';
    echo '<td>';
    echo $product->is_active ? '<span class="ok">active</span>' : '<span class="err">inactive</span>';
    echo '<br>' . ($product->is_new ? 'new ' : '') . ($product->is_featured ? 'featured ' : '');
    echo '</td>';
    echo '<td>' . (int) $audit['image_row_count'];
    if ($audit['image_row_count'] > 0) {
        echo '<br><small>';
        foreach ($audit['image_rows'] as $row) {
            echo '#' . $row['id'] . ' ' . htmlspecialchars((string) $row['image']);
            echo ($row['is_primary'] ? ' <strong>primary</strong>' : '') . '<br>';
        }
        echo '</small>';
    }
    echo '</td>';
    echo '<td><code>' . htmlspecialchars((string) ($audit['primary_path'] ?? '—')) . '</code></td>';
    echo '<td>';
    if ($audit['primary_path'] && ! str_starts_with((string) $audit['primary_path'], 'http')) {
        echo ($audit['storage_exists'] ? '<span class="ok">storage/app/public</span>' : '<span class="err">MISSING</span>') . '<br>';
        echo ($audit['public_file_exists'] ? '<span class="ok">public/storage</span>' : '<span class="warn">not via symlink</span>');
    } else {
        echo '—';
    }
    echo '</td>';
    echo '<td class="' . $statusClass . '">' . htmlspecialchars((string) $audit['message']) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';

$logDir = $basePath . '/storage/logs';
$logFiles = glob($logDir . '/media-upload*.log') ?: [];
rsort($logFiles);
$logFile = $logFiles[0] ?? null;

if ($logFile && is_readable($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
    $storefrontLines = array_values(array_filter($lines, fn ($line) => str_contains($line, 'storefront.')));
    $tail = array_slice($storefrontLines, -40);

    echo '<h2>Recent storefront log entries</h2>';
    echo '<pre style="background:#1e1e1e;padding:16px;overflow:auto;max-height:400px;border:1px solid #333">';
    echo htmlspecialchars(implode("\n", $tail ?: ['No storefront.* log entries yet — visit the homepage first.']));
    echo '</pre>';
}

echo '<p class="meta"><strong>Delete</strong> <code>public/storefront-images-diag.php</code> when done.</p>';
echo '</body></html>';
