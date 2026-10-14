<?php

/**
 * Eternity Gadget — Media / Image Upload Diagnostic (live server)
 *
 * Upload to: public/media-diag.php
 * Visit:    https://yourdomain.com/media-diag.php?key=eternity-admin-2026
 *
 * Optional repair: ?key=...&repair=1  (normalizes bad DB paths)
 * DELETE after fixing images.
 */

declare(strict_types=1);

require __DIR__ . '/admin-diag-lib.php';

adminDiagAuth();
header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = adminDiagInit($basePath);
$results = [];
$repair = isset($_GET['repair']) && $_GET['repair'] === '1';

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Media Diagnostic</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:960px;margin:24px auto;padding:0 16px;background:#f8f8f8;color:#111}';
echo 'h1{font-size:22px}.block{background:#fff;border:1px solid #ddd;padding:16px;margin:16px 0}';
echo '.row{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #eee;font-size:13px}.row:last-child{border:0}';
echo '.badge{flex:0 0 48px;text-align:center;font-weight:700;font-size:12px;padding:4px 0;border-radius:4px}';
echo '.ok .badge{background:#d4edda;color:#155724}.fail .badge{background:#f8d7da;color:#721c24}';
echo '.detail{color:#555;margin-top:4px;word-break:break-word}code{background:#eee;padding:1px 4px;border-radius:3px}';
echo 'table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f0f0f0}</style></head><body>';
echo '<h1>Media / Image Diagnostic</h1>';
echo '<p>Server time: ' . date('Y-m-d H:i:s T') . ' — delete <code>media-diag.php</code> after fixing.</p>';

if ($repair) {
    echo '<p><strong>Repair mode:</strong> normalizing stored image paths in the database.</p>';
}

$publicStorage = $basePath . '/public/storage';
$storagePublic = $basePath . '/storage/app/public';
$linkTarget = is_link($publicStorage) ? readlink($publicStorage) : null;
$linkOk = is_link($publicStorage) && realpath($publicStorage) === realpath($storagePublic);

$results['storage'][] = diagMark($logFile, is_dir($storagePublic), 'storage/app/public exists', $storagePublic);
$results['storage'][] = diagMark($logFile, is_link($publicStorage), 'public/storage symlink exists', $publicStorage);
$results['storage'][] = diagMark(
    $logFile,
    $linkOk,
    'Symlink points to storage/app/public',
    $linkTarget ? "target: {$linkTarget}" : 'Not a symlink — run: php artisan storage:link'
);

foreach (['products', 'categories', 'sliders', 'livewire-tmp'] as $dir) {
    $full = $storagePublic . '/' . $dir;
    $writable = is_dir($full) && is_writable($full);
    $results['storage'][] = diagMark($logFile, $writable, "Writable: {$dir}/", $full);
}

$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';

if (! is_readable($autoload) || ! is_readable($bootstrapApp)) {
    $results['boot'][] = diagMark($logFile, false, 'Laravel bootstrap', 'Missing vendor or bootstrap');
} else {
    require $autoload;
    $app = require $bootstrapApp;
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $results['boot'][] = diagMark($logFile, true, 'Laravel boot', 'OK');

    $results['config'][] = diagMark($logFile, true, 'APP_URL', (string) config('app.url'));
    $results['config'][] = diagMark($logFile, true, 'Public disk URL', (string) config('filesystems.disks.public.url'));

    $checks = [
        ['model' => \App\Models\ProductImage::class, 'label' => 'Product images'],
        ['model' => \App\Models\Category::class, 'label' => 'Categories'],
        ['model' => \App\Models\Slider::class, 'label' => 'Sliders'],
    ];

    echo '<div class="block"><h2>Image records</h2><table><tr><th>Type</th><th>ID</th><th>DB path</th><th>Normalized</th><th>File</th><th>URL</th></tr>';

    $broken = 0;
    $repaired = 0;

    foreach ($checks as $check) {
        if (! class_exists($check['model'])) {
            continue;
        }

        $rows = $check['model']::query()->whereNotNull('image')->where('image', '!=', '')->limit(50)->get();

        foreach ($rows as $row) {
            $raw = $row->getRawOriginal('image');
            $normalized = \App\Support\MediaPath::normalize($raw);
            $filePath = $normalized && ! str_starts_with($normalized, 'http')
                ? $storagePublic . '/' . ltrim($normalized, '/')
                : null;
            $exists = $filePath ? file_exists($filePath) : (bool) $normalized;
            $url = media_url($raw);

            if ($repair && $normalized && $normalized !== $raw) {
                $row->forceFill(['image' => $normalized])->saveQuietly();
                $repaired++;
            }

            if (! $exists) {
                $broken++;
            }

            $status = $exists ? 'ok' : 'fail';
            echo "<tr class=\"{$status}\"><td>{$check['label']}</td><td>{$row->id}</td>";
            echo '<td><code>' . htmlspecialchars(substr((string) $raw, 0, 80)) . '</code></td>';
            echo '<td><code>' . htmlspecialchars((string) $normalized) . '</code></td>';
            echo '<td>' . ($exists ? 'YES' : '<strong>MISSING</strong>') . '</td>';
            echo '<td><a href="' . htmlspecialchars($url) . '" target="_blank">test</a></td></tr>';
        }
    }

    echo '</table>';
    echo '<p>Sampled up to 50 rows per table. Broken/missing files: <strong>' . $broken . '</strong>';
    if ($repair) {
        echo ' — Repaired paths: <strong>' . $repaired . '</strong>';
    }
    echo '</p>';
    if (! $repair && $broken > 0) {
        echo '<p><a href="?key=' . ADMIN_DIAG_SECRET . '&repair=1">Run path repair</a> (fixes JSON/double-storage paths in DB)</p>';
    }
    echo '</div>';

    $testFile = 'products/.diag-test-' . date('Ymd') . '.txt';
    $testWritten = @file_put_contents($storagePublic . '/' . $testFile, 'diag');
    $results['write'][] = diagMark($logFile, $testWritten !== false, 'Write test in products/', $testWritten !== false ? 'OK' : 'FAILED — check folder permissions');
    if ($testWritten !== false) {
        @unlink($storagePublic . '/' . $testFile);
    }
}

foreach ($results as $section => $rows) {
    echo '<div class="block"><h2>' . htmlspecialchars(ucfirst($section)) . '</h2>';
    foreach ($rows as $row) {
        $cls = $row['ok'] ? 'ok' : 'fail';
        echo '<div class="row ' . $cls . '"><span class="badge">' . ($row['ok'] ? 'OK' : 'FAIL') . '</span>';
        echo '<div><strong>' . htmlspecialchars($row['label']) . '</strong>';
        if ($row['detail']) {
            echo '<div class="detail">' . htmlspecialchars($row['detail']) . '</div>';
        }
        echo '</div></div>';
    }
    echo '</div>';
}

echo '<div class="block"><h2>Common fixes</h2><ol style="font-size:14px;line-height:1.7">';
echo '<li>Run <code>php artisan storage:link</code> if symlink is missing.</li>';
echo '<li>Ensure <code>storage/app/public/products</code> is writable (chmod 775).</li>';
echo '<li>Upload updated Filament files with <code>->disk(\'public\')</code> on ImageColumn.</li>';
echo '<li>Run repair above if DB paths are JSON arrays like <code>["products/x.jpg"]</code>.</li>';
echo '<li>Set <code>APP_URL</code> in .env to your real domain (no trailing slash).</li>';
echo '</ol></div>';

echo '<p style="font-size:12px;color:#666">Log: storage/logs/admin-diag.log</p>';
echo '</body></html>';

diagLog($logFile, 'info', '=== Media diagnostic finished ===');
